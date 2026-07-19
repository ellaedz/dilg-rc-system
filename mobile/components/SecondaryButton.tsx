import { PrimaryButton } from '@/components/PrimaryButton';

type SecondaryButtonProps = {
  title: string;
  onPress?: () => void;
  disabled?: boolean;
  loading?: boolean;
  accessibilityLabel?: string;
};

export function SecondaryButton(props: SecondaryButtonProps) {
  return <PrimaryButton {...props} variant="outline" />;
}
