import modelMetadata from '@/assets/models/model_metadata.json';

export const MODEL_INPUT_WIDTH = modelMetadata.tensor_contract.input.shape[2];
export const MODEL_INPUT_HEIGHT = modelMetadata.tensor_contract.input.shape[1];
export const MODEL_OUTPUT_SHAPE = modelMetadata.tensor_contract.output.shape;
export const MODEL_VERSION = modelMetadata.model.version;
export const MODEL_FILENAME = modelMetadata.model.preferred_filename;
export const MODEL_HASH = modelMetadata.models.find((model) => model.filename === MODEL_FILENAME)?.sha256 ?? '';
export const EXPECTED_LABELS = modelMetadata.classes.names;
export const LETTERBOX_COLOR = modelMetadata.preprocessing.letterbox_color_rgb;

export const CONFIDENCE_THRESHOLD = 0.4;
export const ACCEPTED_CONFIDENCE_THRESHOLD = 0.6;
export const HIGH_CONFIDENCE_THRESHOLD = 0.8;
export const IOU_THRESHOLD = 0.45;
export const MAX_DETECTIONS = 20;

export const readableViolationLabels: Record<string, string> = {
  construction_materials: 'Construction Materials Obstruction',
  garbage_debris: 'Garbage/Debris Obstruction',
  illegal_parking: 'Illegal Parking',
  road_obstruction: 'Road Obstruction',
  sidewalk_obstruction: 'Sidewalk Obstruction',
};
