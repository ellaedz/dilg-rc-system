import { AppCard } from '@/components/AppCard';

export function PrivacyNotice() {
  return (
    <AppCard
      icon="PR"
      title="Anonymous and privacy-aware"
      description="Reporting requires no name, email, or home address. Evidence is uploaded only after you press Submit. The private Tracking Token is stored in the device secure store and must not be shared publicly."
      tone="info"
    />
  );
}
