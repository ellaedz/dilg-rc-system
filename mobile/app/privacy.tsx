import { AppCard } from '@/components/AppCard';
import { AppHeader } from '@/components/AppHeader';
import { Screen } from '@/components/Screen';

export default function PrivacyScreen() {
  return (
    <Screen>
      <AppHeader title="Privacy" subtitle="Anonymous citizen reporting principles" />
      <AppCard icon="ANON" title="Anonymous reporting" description="No name, email, home address, login, or registration is required." />
      <AppCard icon="PHOTO" title="Photo evidence" description="Only the photo you choose for the report is uploaded." />
      <AppCard icon="GPS" title="Incident location" description="GPS is used for the reported incident only. Background location is not requested." />
      <AppCard icon="AI" title="Barangay review" description="The system may suggest a classification, but the selected barangay makes the official decision." />
      <AppCard icon="DATA" title="Optional AI improvement" description="Only reports with permission may be considered for a future training dataset after staff verification and privacy review. Permission is not required to submit a report." />
    </Screen>
  );
}
