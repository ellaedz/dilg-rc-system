export const violationTypes = [
  'Illegal Parking',
  'Road Obstruction',
  'Sidewalk Obstruction',
  'Vending Obstruction',
  'Construction Materials Obstruction',
  'Encroachment',
  'Abandoned Vehicle',
  'Illegal Structure',
  'Waste/Garbage Obstruction',
  'Other Road Clearing Violation',
] as const;

export type ViolationType = (typeof violationTypes)[number];
