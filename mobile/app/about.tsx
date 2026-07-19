import { AppCard } from '@/components/AppCard';
import { AppHeader } from '@/components/AppHeader';
import { Screen } from '@/components/Screen';

export default function AboutScreen() {
  return (
    <Screen>
      <AppHeader title="About DILG-RC" subtitle="Road clearing transparency for Santa Cruz, Laguna" />
      <AppCard icon="PURPOSE" title="System purpose" description="DILG-RC supports reporting, monitoring, verification, routing, and transparency for road-clearing violations." />
      <AppCard icon="SCOPE" title="Road-clearing scope" description="The system focuses on illegal parking, road obstructions, sidewalk obstructions, vending, construction materials, encroachment, abandoned vehicles, illegal structures, and waste or garbage obstructions." />
      <AppCard icon="MOBILE" title="Current mobile phase" description="Phase 5C adds on-device TensorFlow Lite image analysis to the existing photo, context, and draft workflow. Citizens do not choose the official classification, and reports are not uploaded yet." />
      <AppCard icon="MAP" title="Coverage" description="Current GIS validation covers Santa Cruz, Laguna at the municipal boundary level. Barangay polygons are still unavailable." />
      <AppCard icon="NEXT" title="Upcoming phase" description="Phase 5D adds GPS incident location and Laravel submission. This app does not fabricate location, submission, tracking, or notification results." />
    </Screen>
  );
}
