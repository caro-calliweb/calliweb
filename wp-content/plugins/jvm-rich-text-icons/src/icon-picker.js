import { __ } from "@wordpress/i18n";
import { useState, useMemo } from "@wordpress/element";
import { Button, TextControl, Tooltip } from "@wordpress/components";

export default function IconPicker({
  icons,
  classPrefix,
  selectedIcon,
  onSelect,
}) {
  const [keyword, setKeyword] = useState("");

  const filteredIcons = useMemo(() => {
    if (!keyword) return icons;
    const lower = keyword.toLowerCase();
    return icons.filter((icon) => icon.toLowerCase().indexOf(lower) !== -1);
  }, [icons, keyword]);

  return (
    <div className="jvm-richtext-icons-picker">
      <TextControl
        __next40pxDefaultSize
        __nextHasNoMarginBottom
        value={keyword}
        placeholder={__("Search", "jvm-rich-text-icons")}
        onChange={setKeyword}
      />
      <div className="jvm-richtext-icons-panel">
        {filteredIcons.length > 0 ? (
          <ul className="jvm-richtext-icons-list">
            {filteredIcons.map((icon) => (
              <li key={icon} data-key={icon}>
                <Tooltip text={icon}>
                  <Button
                    isTertiary
                    className={icon === selectedIcon ? "is-selected" : ""}
                    onClick={() => onSelect(icon)}
                  >
                    <i
                      className={classPrefix + " " + icon}
                      aria-hidden="true"
                    ></i>
                  </Button>
                </Tooltip>
              </li>
            ))}
          </ul>
        ) : (
          <p>{__("No icons found.", "jvm-rich-text-icons")}</p>
        )}
      </div>
    </div>
  );
}
