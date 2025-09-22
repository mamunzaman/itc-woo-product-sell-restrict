/**
 * ITC MCP Admin JavaScript
 */
(function ($) {
  "use strict";

  const IctMcpAdmin = {
    init: function () {
      this.bindEvents();
      this.initializeWysiwyg();
    },

    bindEvents: function () {
      // Bind to WYSIWYG editor changes
      $(document).on("tinymce-editor-init", function (event, editor) {
        if (editor.id === "ict_mcp_restriction_message") {
          editor.on("input change keyup paste", function () {
            IctMcpAdmin.activateSaveButton();
          });
        }
      });

      // Bind to textarea changes (for non-visual mode)
      $(document).on(
        "input change keyup",
        "#ict_mcp_restriction_message",
        function () {
          IctMcpAdmin.activateSaveButton();
        }
      );

      // Bind to form changes
      $(document).on(
        "change input",
        "form.woocommerce-settings-form input, form.woocommerce-settings-form select, form.woocommerce-settings-form textarea",
        function () {
          IctMcpAdmin.activateSaveButton();
        }
      );
    },

    initializeWysiwyg: function () {
      // Ensure TinyMCE is loaded and initialize our field
      if (typeof tinymce !== "undefined") {
        this.setupWysiwygField();
      } else {
        // Wait for TinyMCE to load
        setTimeout(() => {
          this.initializeWysiwyg();
        }, 100);
      }
    },

    setupWysiwygField: function () {
      const editorId = "ict_mcp_restriction_message";

      if (tinymce.get(editorId)) {
        const editor = tinymce.get(editorId);

        // Add change event listener
        editor.on("input change keyup paste undo redo", function () {
          IctMcpAdmin.activateSaveButton();
        });

        // Handle visual/text mode switching
        editor.on("show hide", function () {
          IctMcpAdmin.activateSaveButton();
        });
      }
    },

    activateSaveButton: function () {
      const $saveButton = $(".woocommerce-save-button");
      const $form = $("form.woocommerce-settings-form");

      if ($saveButton.length && $form.length) {
        // Enable the save button
        $saveButton.prop("disabled", false);

        // Add visual indication that form has changes
        $form.addClass("has-changes");

        // Mark form as needing save
        $form.data("needs-save", true);
      }
    },

    deactivateSaveButton: function () {
      const $saveButton = $(".woocommerce-save-button");
      const $form = $("form.woocommerce-settings-form");

      if ($saveButton.length && $form.length) {
        $saveButton.prop("disabled", true);
        $form.removeClass("has-changes");
        $form.data("needs-save", false);
      }
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    IctMcpAdmin.init();
  });

  // Also initialize on WooCommerce settings page load
  $(document).on("woocommerce_settings_page_loaded", function () {
    IctMcpAdmin.init();
  });

  // Handle form submission
  $(document).on("submit", "form.woocommerce-settings-form", function () {
    // Ensure WYSIWYG content is saved
    if (typeof tinymce !== "undefined") {
      tinymce.triggerSave();
    }
  });
})(jQuery);
