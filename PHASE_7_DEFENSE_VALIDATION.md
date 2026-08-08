# Phase 7 Defense Stabilization and Validation

This phase adds no product feature. It records the verified defense workflow and the
remaining physical-device acceptance evidence.

## Stabilization fixes

- Public mobile tracking now reads `latest_action` from the final chronological timeline
  entry instead of accidentally returning the original submission entry.
- The Git-root `.easignore` prevents Windows EAS archiving from traversing Laravel,
  FastAPI virtual environments, and pytest caches while retaining the complete mobile
  app, Float16 TFLite model, and labels.
- The Android preview profile produces an APK and uses the remote EAS app version source.

## Automated evidence

- FastAPI: 5 tests passed with the real joblib NLP artifact.
- Laravel: 22 tests passed with 145 assertions, including the complete defense workflow.
- Mobile: TypeScript, ESLint, and 11 Jest tests passed.
- Expo Doctor: 18 of 18 checks passed.
- Android/Hermes export: passed; `best_float16.tflite` and `labels.txt` were bundled.
- Vite production dashboard build: passed.
- Local EAS archive inspection: passed and retained the mobile model artifacts.

## Defense startup

FastAPI terminal:

```powershell
cd C:\Users\63923\Desktop\database\htdocs\DILG-RC\ai-inference-server
.\.venv\Scripts\Activate.ps1
uvicorn main:app --host 0.0.0.0 --port 9000
```

Laravel terminal:

```powershell
cd C:\Users\63923\Desktop\database\htdocs\DILG-RC
php artisan serve --host=0.0.0.0 --port=8000
```

Development-client Metro terminal, only when using a development APK:

```powershell
cd C:\Users\63923\Desktop\database\htdocs\DILG-RC\mobile
npx expo start --dev-client --lan -c
```

The preview APK contains its JavaScript bundle and does not require Metro. The phone and
laptop must use the same Wi-Fi. The configured defense API is
`http://192.168.1.5:8000/api`; confirm the laptop IPv4 address has not changed.

## Physical end-to-end acceptance record

Record Pass/Fail and evidence during the defense rehearsal. Do not mark a step passed
without observing it on the real phone or web interface.

| Step | Expected evidence | Result |
| --- | --- | --- |
| 1–2 | FastAPI `/health` returns `status=ok` and `nlp_model_loaded=true` | Pending device rehearsal |
| 3 | Laravel `/up` returns HTTP 200 | Pending device rehearsal |
| 4 | Installed custom development/preview APK opens | Pending device rehearsal |
| 5–7 | Real photo runs TFLite and displays class, confidence, and inference status | Pending physical test |
| 8–10 | Description and foreground GPS validate inside Santa Cruz | Pending physical test |
| 11–13 | Submission returns `RCV-YYYY-NNNN` and persists the report | Pending physical test |
| 14–16 | DILG dashboard/details show photo, GPS, image, NLP, fusion, and AI status fields | Pending rehearsal |
| 17 | DILG manually routes the unresolved barangay with reason and confirmation | Pending rehearsal |
| 18–19 | Assigned barangay account sees the report | Pending rehearsal |
| 20 | Barangay status/action update succeeds | Pending rehearsal |
| 21–24 | Mobile tracking refresh shows the same Tracking ID, status, and latest action | Pending physical test |

## Known data limitations

- Barangay polygons are unavailable; DILG manual routing is the correct defense flow.
- Some barangay office coordinates remain provisional and are recommendations only.
- NLP confidence must be presented as advisory; the supplied evaluation set is small.
- Physical TFLite execution, GPS, camera/gallery behavior, and phone-to-LAN connectivity
  cannot be certified by desktop automation.
