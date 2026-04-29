/* global a404Presets, a404CurrentMode, jQuery */
jQuery(document).ready(function($) {
  // Global state
  const state = {
    preset: {
      selected: '',
      username: '',
      password: ''
    },
    custom: {
      host: '',
      port: '587',
      encryption: 'tls',
      username: '',
      password: ''
    },
    common: {
      fromEmail: '',
      fromName: ''
    }
  };

  // Accordion state
  const accordionState = {
    preset: false,
    custom: false
  };

  // Initialize on page load
  function init() {
    loadInitialState();
    setupAccordionToggles();
    setupPresetChangeListener();
    setupCustomFieldListeners();
    setupCommonFieldListeners();
    updateAllSummaries();
  }

  // Load initial state from form fields
  function loadInitialState() {
    // Load preset state
    state.preset.selected = $('#404-preset-id').val() || '';
    state.preset.username = $('#404-preset-username').val() || '';
    state.preset.password = $('#404-preset-password').val() || '';

    // Load custom state
    state.custom.host = $('#404-custom-host').val() || '';
    state.custom.port = $('#404-custom-port').val() || '587';
    state.custom.encryption = $('#404-custom-encryption').val() || 'tls';
    state.custom.username = $('#404-custom-username').val() || '';
    state.custom.password = $('#404-custom-password').val() || '';

    // Load common state
    state.common.fromEmail = $('#404-from-email').val() || '';
    state.common.fromName = $('#404-from-name').val() || '';

    // Determine which accordion was active based on provider_id
    if (a404CurrentMode === 'preset' && state.preset.selected) {
      accordionState.preset = true;
    } else if (a404CurrentMode === 'custom' || (state.custom.host && !state.preset.selected)) {
      accordionState.custom = true;
    }

    // Open the appropriate accordion
    if (accordionState.preset) {
      openAccordion('preset');
    } else if (accordionState.custom) {
      openAccordion('custom');
    }
  }

  // Setup accordion toggle buttons
  function setupAccordionToggles() {
    $('.404-accordion-toggle').on('click', function(e) {
      e.preventDefault();
      const target = $(this).data('accordion');
      if (accordionState[target]) {
        closeAccordion(target);
      } else {
        openAccordion(target);
      }
    });
  }

  function openAccordion(type) {
    accordionState[type] = true;
    const content = $('#404-accordion-' + type);
    const toggle = $('[data-accordion="' + type + '"]');

    content.slideDown(300);
    toggle.find('.404-accordion-icon').text('➖');
  }

  function closeAccordion(type) {
    accordionState[type] = false;
    const content = $('#404-accordion-' + type);
    const toggle = $('[data-accordion="' + type + '"]');

    content.slideUp(300);
    toggle.find('.404-accordion-icon').text('➕');
  }

  // Listen for preset selection changes
  function setupPresetChangeListener() {
    $('#404-preset-id').on('change', function() {
      const key = $(this).val();
      state.preset.selected = key;

      if (key && a404Presets[key]) {
        const preset = a404Presets[key];
        // Fill hidden inputs with preset data
        $('#404-preset-host').val(preset.host);
        $('#404-preset-port').val(preset.port);
        $('#404-preset-encryption').val(preset.encryption);

        // Display preset info if available
        if (preset.info) {
          $('#404-preset-info').html(preset.info).show();
        } else {
          $('#404-preset-info').hide();
        }
      } else {
        // Clear preset data if no selection
        $('#404-preset-host').val('');
        $('#404-preset-port').val('');
        $('#404-preset-encryption').val('');
        $('#404-preset-info').hide();
      }

      updateAllSummaries();
    });
  }

  // Listen for custom field changes
  function setupCustomFieldListeners() {
    $('#404-custom-host').on('input', function() {
      state.custom.host = $(this).val();
      updateAllSummaries();
    });

    $('#404-custom-port').on('input change', function() {
      state.custom.port = $(this).val() || '587';
      updateAllSummaries();
    });

    $('#404-custom-encryption').on('change', function() {
      state.custom.encryption = $(this).val();
      updateAllSummaries();
    });

    $('#404-custom-username').on('input', function() {
      state.custom.username = $(this).val();
      updateAllSummaries();
    });

    $('#404-custom-password').on('input', function() {
      state.custom.password = $(this).val();
      updateAllSummaries();
    });
  }

  // Listen for common field changes
  function setupCommonFieldListeners() {
    $('#404-from-email').on('input', function() {
      state.common.fromEmail = $(this).val();
      updateAllSummaries();
    });

    $('#404-from-name').on('input', function() {
      state.common.fromName = $(this).val();
      updateAllSummaries();
    });
  }

  // Update all summary displays
  function updateAllSummaries() {
    updatePresetSummary();
    updateCustomSummary();
    updateMainSummary();
  }

  // Update preset accordion summary
  function updatePresetSummary() {
    const key = state.preset.selected;
    if (key && a404Presets[key]) {
      const preset = a404Presets[key];
      $('#preset-summary-host').text(preset.host);
      $('#preset-summary-port').text(preset.port);
      $('#preset-summary-encryption').text(preset.encryption);
    } else {
      $('#preset-summary-host').text('—');
      $('#preset-summary-port').text('—');
      $('#preset-summary-encryption').text('—');
    }
  }

  // Update custom accordion summary
  function updateCustomSummary() {
    $('#custom-summary-host').text(state.custom.host || '—');
    $('#custom-summary-port').text(state.custom.port || '—');
    $('#custom-summary-encryption').text(state.custom.encryption || '—');
    $('#custom-summary-username').text(state.custom.username || '—');
    $('#custom-summary-password').text(state.custom.password ? '***' : '—');
  }

  // Update main summary (at bottom)
  function updateMainSummary() {
    let host, port, encryption, username;

    if (accordionState.preset && state.preset.selected && a404Presets[state.preset.selected]) {
      const preset = a404Presets[state.preset.selected];
      host = preset.host;
      port = preset.port;
      encryption = preset.encryption;
      username = state.preset.username;
    } else if (accordionState.custom) {
      host = state.custom.host;
      port = state.custom.port;
      encryption = state.custom.encryption;
      username = state.custom.username;
    } else {
      host = port = encryption = username = '—';
    }

    const fromEmail = state.common.fromEmail || '—';
    const fromName = state.common.fromName || '—';

    $('#summary-host').text(host);
    $('#summary-port').text(port);
    $('#summary-encryption').text(encryption);
    $('#summary-username').text(username);
    $('#summary-from-email').text(fromEmail);
    $('#summary-from-name').text(fromName);
  }

  // Initialize on document ready
  init();
});
