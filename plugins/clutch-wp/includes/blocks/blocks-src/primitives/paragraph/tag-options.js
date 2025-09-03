// Tag options for the Paragraph block tag picker organized by priority

// High priority tags (priority 5) - Override core blocks
const HIGH_PRIORITY_OPTIONS = [
  { tag: 'h1', label: 'Heading 1' },
  { tag: 'h2', label: 'Heading 2' },
  { tag: 'h3', label: 'Heading 3' },
  { tag: 'h4', label: 'Heading 4' },
  { tag: 'h5', label: 'Heading 5' },
  { tag: 'h6', label: 'Heading 6' },
];

// Default priority tags (priority 20)
const DEFAULT_PRIORITY_OPTIONS = [{ tag: 'p', label: 'Paragraph' }];

// Regular priority tags (no explicit priority, default 20)
const REGULAR_PRIORITY_OPTIONS = [
  { tag: 'b', label: 'Bold' },
  { tag: 'code', label: 'Code' },
  { tag: 'i', label: 'Italic' },
  { tag: 'q', label: 'Quote' },
  { tag: 's', label: 'Strikethrough' },
  { tag: 'span', label: 'Span' },
  { tag: 'u', label: 'Underline' },
];

// Combined options for the dropdown (sorted alphabetically)
export const TAG_OPTIONS = [
  ...HIGH_PRIORITY_OPTIONS,
  ...DEFAULT_PRIORITY_OPTIONS,
  ...REGULAR_PRIORITY_OPTIONS,
].sort((a, b) => a.label.localeCompare(b.label));

// Priority constants
export const HIGH_PRIORITY = 5;
export const DEFAULT_PRIORITY = 20;
export const MULTILINE_TEXT_PRIORITY = 10; // Lower priority than heading transforms

// Tag arrays for transforms (extracted from the option groups)
export const HIGH_PRIORITY_TAGS = HIGH_PRIORITY_OPTIONS.map(
  option => option.tag
);
export const DEFAULT_PRIORITY_TAGS = DEFAULT_PRIORITY_OPTIONS.map(
  option => option.tag
);
export const REGULAR_PRIORITY_TAGS = REGULAR_PRIORITY_OPTIONS.map(
  option => option.tag
);

// Schema creation helper
export function createSchemaForTags(tags, phrasingContentSchema, isPaste) {
  const schema = {};

  tags.forEach(tag => {
    if (isPaste) {
      schema[tag] = {
        children: phrasingContentSchema,
        attributes: [],
      };
    } else {
      schema[tag] = {
        children: phrasingContentSchema,
      };
    }
  });

  return schema;
}
