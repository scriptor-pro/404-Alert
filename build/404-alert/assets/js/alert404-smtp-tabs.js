/* Minimal SMTP Tabs Handler */
jQuery(document).ready(function($) {
  // Presets data from PHP
  const presets = window.a404Presets || {};

  // Tab switching
  function updateTabsDisplay() {
    const mode = $('input[name="404_smtp_mode"]:checked').val();
    if (mode === 'preset') {
      $('.404-tab-content').eq(0).show();
      $('.404-tab-content').eq(1).hide();
    } else {
      $('.404-tab-content').eq(0).hide();
      $('.404-tab-content').eq(1).show();
    }
  }

  // Listen to tab changes
  $('input[name="404_smtp_mode"]').on('change', updateTabsDisplay);

  // Initialize tabs display on page load
  updateTabsDisplay();

  // Update preset summary when preset changes
  $('#404-preset-id').on('change', function() {
    const key = $(this).val();
    if (key && presets[key]) {
      const p = presets[key];
      $('#preset-summary-host').text(p.host);
      $('#preset-summary-port').text(p.port);
      $('#preset-summary-encryption').text(p.encryption);
      if (p.info) {
        $('#404-preset-info').html(p.info).show();
      } else {
        $('#404-preset-info').hide();
      }
    } else {
      $('#preset-summary-host').text('—');
      $('#preset-summary-port').text('—');
      $('#preset-summary-encryption').text('—');
      $('#404-preset-info').hide();
    }
    updateMainSummary();
  });

  // Update custom summary on field changes
  $('#404-custom-host').on('input', function() {
    $('#custom-summary-host').text($(this).val() || '—');
    updateMainSummary();
  });

  $('#404-custom-port').on('input change', function() {
    $('#custom-summary-port').text($(this).val() || '—');
    updateMainSummary();
  });

  $('#404-custom-encryption').on('change', function() {
    $('#custom-summary-encryption').text($(this).val() || '—');
    updateMainSummary();
  });

  $('#404-custom-username').on('input', function() {
    $('#custom-summary-username').text($(this).val() || '—');
    updateMainSummary();
  });

  $('#404-custom-password').on('input', function() {
    $('#custom-summary-password').text($(this).val() ? '***' : '—');
    updateMainSummary();
  });

  // Update preset summary on username change
  $('#404-preset-username').on('input', function() {
    updateMainSummary();
  });

  // Update preset summary on password change
  $('#404-preset-password').on('input', function() {
    updateMainSummary();
  });

  // Common fields
  $('#404-from-email').on('input', function() {
    $('#summary-from-email').text($(this).val() || '—');
  });

  $('#404-from-name').on('input', function() {
    $('#summary-from-name').text($(this).val() || '—');
  });

  // Update main summary based on active tab
  function updateMainSummary() {
    const mode = $('input[name="404_smtp_mode"]:checked').val();

    if (mode === 'preset') {
      const key = $('#404-preset-id').val();
      if (key && presets[key]) {
        const p = presets[key];
        $('#summary-host').text(p.host);
        $('#summary-port').text(p.port);
        $('#summary-encryption').text(p.encryption);
        $('#summary-username').text($('#404-preset-username').val() || '—');
      } else {
        $('#summary-host').text('—');
        $('#summary-port').text('—');
        $('#summary-encryption').text('—');
        $('#summary-username').text('—');
      }
    } else {
      $('#summary-host').text($('#404-custom-host').val() || '—');
      $('#summary-port').text($('#404-custom-port').val() || '—');
      $('#summary-encryption').text($('#404-custom-encryption').val() || '—');
      $('#summary-username').text($('#404-custom-username').val() || '—');
    }
  }

  // Initialize summaries
  updateMainSummary();
  $('#404-preset-id').change();
  $('#404-custom-host').trigger('input');
  $('#404-custom-port').trigger('input');
  $('#404-custom-encryption').change();
  $('#404-custom-username').trigger('input');
  $('#404-from-email').trigger('input');
  $('#404-from-name').trigger('input');
});
