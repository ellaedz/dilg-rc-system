from __future__ import annotations

import os
import re
import uuid
from typing import Any

import jwt
from jwt import PyJWKClient


class EntraAuthorizationError(Exception):
    def __init__(self, status_code: int) -> None:
        super().__init__("The inference request is not authorized.")
        self.status_code = status_code


class EntraAuthenticator:
    def __init__(self) -> None:
        self.mode = os.getenv("FASTAPI_AUTH_MODE", "none").strip().lower()
        self.tenant_id = os.getenv("AZURE_TENANT_ID", "").strip().lower()
        self.audience = os.getenv("FASTAPI_ENTRA_AUDIENCE", "").strip()
        self.expected_client_id = os.getenv(
            "AZURE_LARAVEL_MANAGED_IDENTITY_CLIENT_ID", ""
        ).strip().lower()
        self.expected_principal_id = os.getenv(
            "AZURE_LARAVEL_MANAGED_IDENTITY_PRINCIPAL_ID", ""
        ).strip().lower()
        self.required_role = os.getenv(
            "FASTAPI_ENTRA_ROLE", "Civiclear.FastApi.Invoke"
        ).strip()
        self._jwks: dict[str, PyJWKClient] = {}

    @property
    def enabled(self) -> bool:
        return self.mode == "azure_entra"

    def assert_configuration(self) -> None:
        if self.mode == "none":
            return
        if self.mode != "azure_entra":
            raise RuntimeError("Unsupported FastAPI authentication mode.")
        for value in (
            self.tenant_id,
            self.expected_client_id,
            self.expected_principal_id,
        ):
            try:
                uuid.UUID(value)
            except (ValueError, AttributeError) as exc:
                raise RuntimeError("FastAPI Entra configuration is incomplete.") from exc
        if not self.audience or not self.required_role:
            raise RuntimeError("FastAPI Entra configuration is incomplete.")

    def verify(self, authorization: str | None) -> dict[str, Any]:
        if not self.enabled:
            return {}
        if not isinstance(authorization, str):
            raise EntraAuthorizationError(401)
        match = re.fullmatch(r"Bearer ([A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+)", authorization)
        if match is None:
            raise EntraAuthorizationError(401)

        try:
            claims = self._decode(match.group(1))
        except Exception as exc:
            raise EntraAuthorizationError(403) from exc

        version = str(claims.get("ver") or "")
        try:
            issuer = self._issuer(version)
        except ValueError as exc:
            raise EntraAuthorizationError(403) from exc
        client_id = str(claims.get("azp") or claims.get("appid") or "").lower()
        principal_id = str(claims.get("oid") or "").lower()
        roles = claims.get("roles")
        if (
            claims.get("iss") != issuer
            or str(claims.get("tid") or "").lower() != self.tenant_id
            or client_id != self.expected_client_id
            or principal_id != self.expected_principal_id
            or not isinstance(roles, list)
            or self.required_role not in roles
        ):
            raise EntraAuthorizationError(403)

        return claims

    def _decode(self, token: str) -> dict[str, Any]:
        unverified = jwt.decode(
            token,
            options={
                "verify_signature": False,
                "verify_aud": False,
                "verify_exp": False,
                "verify_iat": False,
                "verify_nbf": False,
            },
        )
        if not isinstance(unverified, dict):
            raise ValueError("Invalid Entra token claims.")

        version = str(unverified.get("ver") or "")
        issuer = self._issuer(version)
        jwks = self._jwks.get(version)
        if jwks is None:
            key_path = "discovery/keys" if version == "1.0" else "discovery/v2.0/keys"
            jwks = PyJWKClient(
                f"https://login.microsoftonline.com/{self.tenant_id}/{key_path}",
                cache_keys=True,
                lifespan=3600,
            )
            self._jwks[version] = jwks

        signing_key = jwks.get_signing_key_from_jwt(token)
        claims = jwt.decode(
            token,
            signing_key.key,
            algorithms=["RS256"],
            audience=self.audience,
            issuer=issuer,
            leeway=60,
            options={"require": ["aud", "exp", "iat", "iss", "nbf", "tid", "ver"]},
        )
        if not isinstance(claims, dict):
            raise ValueError("Invalid Entra token claims.")
        return claims

    def _issuer(self, version: str) -> str:
        if version == "1.0":
            return f"https://sts.windows.net/{self.tenant_id}/"
        if version == "2.0":
            return f"https://login.microsoftonline.com/{self.tenant_id}/v2.0"
        raise ValueError("Unsupported Entra token version.")
