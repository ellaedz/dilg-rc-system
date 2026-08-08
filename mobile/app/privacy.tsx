import { AppCard } from '@/components/AppCard';
import { AppHeader } from '@/components/AppHeader';
import { PrivacyNotice } from '@/components/PrivacyNotice';
import { Screen } from '@/components/Screen';

export default function PrivacyScreen() {
  return (
    <Screen>
      <AppHeader title="Privacy" subtitle="Anonymous citizen reporting principles" />
      <PrivacyNotice />
      <AppCard icon="CAM" title="Camera" description="The camera opens only after the citizen taps Take Photo. There is no video, background camera use, facial recognition, or continuous camera-frame inference." />
      <AppCard icon="PHOTO" title="Gallery" description="The app accesses selected photos only when the citizen chooses an image as road-clearing evidence. It does not modify the original gallery photo." />
      <AppCard icon="LOCAL" title="Crash-safe local recovery" description="A prepared request uses an app-owned photograph snapshot and stable Idempotency-Key. Interrupted requests are shown for an explicit retry and are never silently uploaded." />
      <AppCard icon="AI" title="Server AI assessment" description="The phone sends evidence without a trusted category or confidence. The server may suggest a possible violation, but only authorized staff can verify it." />
      <AppCard icon="GPS" title="Incident location only" description="Foreground GPS must refer to the incident location, not the citizen's home address. Background location is not requested." />
      <AppCard icon="ID" title="Private Tracking Token" description="The opaque case-sensitive token is stored with the operating system secure store. Report Numbers and local record IDs are not accepted as anonymous tracking credentials." />
    </Screen>
  );
}
