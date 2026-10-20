import { __ } from "@wordpress/i18n";
import { Component, Fragment } from "@wordpress/element";
import { compose } from "@wordpress/compose";
import { BlockControls } from "@wordpress/block-editor";
import { toggleFormat, insert } from "@wordpress/rich-text";
import { ToolbarGroup, Popover, ToolbarButton } from "@wordpress/components";

import IconPicker from "./icon-picker";

let Icons = jvm_richtext_icon_settings.iconset;
let classPrefix = jvm_richtext_icon_settings.base_class;

class IconMap extends Component {
  constructor() {
    super(...arguments);

    this.toggle = this.toggle.bind(this);

    this.state = {
      isOpen: false,
    };
  }

  toggle() {
    this.setState((state) => ({
      isOpen: !state.isOpen,
    }));
  }

  render() {
    const { isOpen } = this.state;
    const { name, value, onChange } = this.props;

    return (
      <Fragment>
        <BlockControls>
          <ToolbarGroup>
            <ToolbarButton
              icon={"flag"}
              aria-haspopup="true"
              label={__("Insert Icon", "jvm-rich-text-icons")}
              onClick={this.toggle}
            ></ToolbarButton>

            {isOpen && (
              <Popover
                className="jvm-richtext-icons-popover"
                position="bottom left"
                key="icon-popover"
                onClick={() => {}}
                expandOnMobile={false}
                headerTitle={__("Insert Icon", "jvm-rich-text-icons")}
                onClose={() => {
                  onChange(toggleFormat(value, { type: name }));
                }}
              >
                <IconPicker
                  icons={Icons}
                  classPrefix={classPrefix}
                  onSelect={(iconName) => {
                    // Insert the icon as a zero-width space (U+200B) carrying
                    // the icon format. A plain space does NOT survive:
                    // RichText collapses whitespace-only icon content on parse
                    // (collapseWhiteSpace) and then drops the now-empty format,
                    // which breaks block validation (e.g. in core/button).
                    // U+200B is invisible, survives parse + serialize exactly,
                    // and keeps the <i> element in the saved HTML.
                    onChange(
                      insert(value, {
                        formats: [
                          [
                            {
                              type: name,
                              attributes: {},
                              unregisteredAttributes: {
                                class: classPrefix + " " + iconName,
                                "aria-hidden": "true",
                              },
                            },
                          ],
                        ],
                        replacements: [],
                        text: "\u200B",
                      }),
                    );
                    this.toggle();
                  }}
                />
              </Popover>
            )}
          </ToolbarGroup>
        </BlockControls>
      </Fragment>
    );
  }
}

export default compose()(IconMap);
