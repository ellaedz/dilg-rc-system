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
      <AppCard icon="LOCAL" title="Local draft storage" description="Optional text, processed image URI, timestamp, AI suggestion, confidence, detections, and model identity are stored locally. Citizen identity and citizen-selected violation categories are not stored." />
      <AppCard icon="AI" title="AI-assisted analysis" description="TensorFlow Lite runs on the device only after Analyze Photo is tapped. The result is a suggestion for staff review, not an official decision." />
      <AppCard icon="GPS" title="Incident location only" description="GPS capture is not active yet. When added later, it must refer to the incident location, not the citizen's home address." />
      <AppCard icon="ID" title="Tracking ID required" description="The Tracking ID is the citizen's way to check report status. Without it, anonymous reports cannot be personally recovered." />
    </Screen>
  );
}
