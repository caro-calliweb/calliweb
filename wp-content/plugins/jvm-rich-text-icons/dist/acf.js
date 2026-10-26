(function ($) {
  if (typeof acf === "undefined") return;

  function select2_init_args(element, parent) {
    return {
      key: $(parent).data("key"),
      allowNull: $(element).data("allow_null"),
      ajax: 1,
      ajaxAction: "acf/fields/jvm-richtext-insert-icons/query",
    };
  }

  function select2_init(fa_field) {
    var $select = $(fa_field);
    var parent = $($select).closest(".acf-field-font-awesome");

    acf.select2.init($select, select2_init_args(fa_field, parent), parent);
  }

  acf.add_action("select2_init", function ($input, args, settings, $field) {
    if (
      $field instanceof jQuery &&
      $field.hasClass("jvm_rich_text_icon-edit")
    ) {
      $field.addClass("select2_initalized");
    }
  });

  // Add our classes to FontAwesome select2 fields
  acf.add_filter("select2_args", function (args, $select, settings, $field) {
    if ($select.hasClass("select2-jvm_rich_text_icon")) {
      args.dropdownCssClass = "fa-select2-drop fa" + ACFFA.major_version;
      args.containerCssClass = "fa-select2 fa" + ACFFA.major_version;
      args.escapeMarkup = function (markup) {
        if (typeof markup !== "string") {
          return markup;
        }
        return acf.escHtml(markup);
      };
    }

    return args;
  });

  // Uncheck standard icon set choices if 'custom icon set' is checked, and show the custom icon set select box
  $(document).on(
    "change",
    '.acf-field[data-name="icon_sets"] input[type="checkbox"]',
    function () {
      var parent = $(this).closest(".acf-field-object-font-awesome");
      if ($(this).is('[value="custom"]') && $(this).is(":checked")) {
        $('input[type="checkbox"]:not([value="custom"])', parent).prop(
          "checked",
          false,
        );
        $(".acf-field-setting-custom_icon_set", parent).show();
      } else {
        $('input[type="checkbox"][value="custom"]', parent).prop(
          "checked",
          false,
        );
        $(".acf-field-setting-custom_icon_set", parent).hide();
      }
    },
  );

  // Handle new menu items with FontAwesome fields assigned to them
  $(document).on("menu-item-added", function (event, $menuMarkup) {
    var $fields = $(
      "select.jvm_rich_text_icon-edit:not(.select2_initalized)",
      $menuMarkup,
    );

    if ($fields.length) {
      $fields.each(function (index, field) {
        select2_init(field);
      });
    }
  });

  // Update field previews and init select2 in field edit area
  acf.add_action(
    "ready_field/type=jvm_rich_text_icon append_field/type=jvm_rich_text_icon show_field/type=jvm_rich_text_icon",
    function ($el) {
      var $fields = $(
        "select.select2-jvm-rich-text-icon-edit:not(.select2_initalized)",
        $el,
      );

      if ($fields.length) {
        $fields.each(function (index, field) {
          select2_init(field);
        });
      }
    },
  );
})(jQuery);
