import { __ } from "@wordpress/i18n";
import { useDispatch, useSelect } from "@wordpress/data";
import { BlockControls, store as blockEditorStore } from "@wordpress/block-editor";
import { ToolbarButton, ToolbarGroup } from "@wordpress/components";

const PATTERN_OVERRIDES_SOURCE = "core/pattern-overrides";

/**
 * WordPress core's built-in "Reset override" toolbar control is still
 * hardcoded to a fixed list of block types (paragraph, heading, button,
 * image), so it never appears for custom blocks like this one. This
 * reimplements just that control for the Single icon block, using the same
 * public data-store APIs core uses internally.
 *
 * The "Enable overrides" / "Disable overrides" half used to live here too,
 * but WordPress now shows that control generically for any block exposed via
 * `block_bindings_supported_attributes`, so it was removed to avoid a
 * duplicate control in the Advanced panel.
 */
export default function PatternOverridesControl({ clientId, attributes }) {
  const { updateBlockAttributes } = useDispatch(blockEditorStore);

  const { patternClientId, overrides } = useSelect(
    (select) => {
      const { getBlockParentsByBlockName, getBlockAttributes } = select(blockEditorStore);
      const [parentId] = getBlockParentsByBlockName(clientId, "core/block", true);
      return {
        patternClientId: parentId,
        overrides: parentId ? getBlockAttributes(parentId)?.content : undefined,
      };
    },
    [clientId],
  );

  if (!patternClientId) {
    return null;
  }

  const name = attributes.metadata?.name;
  const hasOverridesEnabled = attributes.metadata?.bindings?.__default?.source === PATTERN_OVERRIDES_SOURCE;
  const isOverridden = !!name && !!overrides && Object.prototype.hasOwnProperty.call(overrides, name);
  if (!hasOverridesEnabled || !isOverridden) {
    return null;
  }

  return (
    <BlockControls>
      <ToolbarGroup>
        <ToolbarButton
          icon="undo"
          label={__("Reset override", "jvm-rich-text-icons")}
          onClick={() => {
            const newOverrides = { ...overrides };
            delete newOverrides[name];
            updateBlockAttributes(patternClientId, { content: newOverrides });
          }}
        />
      </ToolbarGroup>
    </BlockControls>
  );
}
