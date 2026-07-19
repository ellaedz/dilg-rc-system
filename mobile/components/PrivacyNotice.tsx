import { AppCard } from '@/components/AppCard';

export function PrivacyNotice() {
  return (
    <AppCard
      icon="PR"
      title="Anonymous and local-first"
      description="Reporting is anonymous. Your name, email, and home address are not required. The photo and location must relate to the road-clearing incident. Photo evidence will be sent only after the citizen confirms and submits the report in a later phase."
      tone="info"
    />
  );
}
