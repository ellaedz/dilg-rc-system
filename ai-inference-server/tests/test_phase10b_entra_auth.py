import pytest
from fastapi.testclient import TestClient

from services.entra_auth import EntraAuthenticator, EntraAuthorizationError


TENANT = "44444444-4444-4444-8444-444444444444"
CLIENT = "11111111-1111-4111-8111-111111111111"
PRINCIPAL = "55555555-5555-4555-8555-555555555555"
ROLE = "Civiclear.FastApi.Invoke"


class ControlledAuthenticator(EntraAuthenticator):
    def __init__(self, claims: dict):
        self.mode = "azure_entra"
        self.tenant_id = TENANT
        self.audience = "api://civiclear-fastapi"
        self.expected_client_id = CLIENT
        self.expected_principal_id = PRINCIPAL
        self.required_role = ROLE
        self._claims = claims
        self._jwks = None

    def _decode(self, token: str) -> dict:
        assert token == "header.payload.signature"
        return self._claims


def valid_claims() -> dict:
    return {
        "iss": f"https://login.microsoftonline.com/{TENANT}/v2.0",
        "tid": TENANT,
        "azp": CLIENT,
        "oid": PRINCIPAL,
        "roles": [ROLE],
    }


def test_entra_authenticator_binds_application_and_managed_identity_claims():
    claims = ControlledAuthenticator(valid_claims()).verify(
        "Bearer header.payload.signature"
    )

    assert claims["oid"] == PRINCIPAL


@pytest.mark.parametrize(
    ("claim", "value"),
    [
        ("tid", "66666666-6666-4666-8666-666666666666"),
        ("azp", "66666666-6666-4666-8666-666666666666"),
        ("oid", "66666666-6666-4666-8666-666666666666"),
        ("roles", ["Unapproved.Role"]),
    ],
)
def test_entra_authenticator_rejects_wrong_identity_claims(claim, value):
    claims = valid_claims()
    claims[claim] = value

    with pytest.raises(EntraAuthorizationError) as error:
        ControlledAuthenticator(claims).verify("Bearer header.payload.signature")

    assert error.value.status_code == 403


def test_entra_authenticator_rejects_missing_bearer_token():
    with pytest.raises(EntraAuthorizationError) as error:
        ControlledAuthenticator(valid_claims()).verify(None)

    assert error.value.status_code == 401


def test_all_inference_routes_are_protected_while_health_stays_available(monkeypatch):
    import main

    def reject(_authorization):
        raise EntraAuthorizationError(401)

    with TestClient(main.app) as client:
        monkeypatch.setattr(main.entra_authenticator, "verify", reject)
        health = client.get("/health")
        legacy = client.post("/predict/text", json={"text_report": "test"})
        versioned = client.post("/v1/predict/image")

    assert health.status_code == 200
    assert legacy.status_code == 401
    assert versioned.status_code == 401
    assert legacy.json()["error"]["code"] == "inference_not_authorized"
