/* global a404Presets, jQuery */
jQuery(document).ready(function($) {
  const colLeft  = $('#404-smtp-col-left');
  const colRight = $('#404-smtp-col-right');
  const presetSelect = $('#404-preset-select');
  const presetInfo = $('#404-preset-info');

  // Gestion du changement de preset
  presetSelect.on('change', function() {
    const key = $(this).val();
    if (key && a404Presets[key]) {
      activateLeft(key);
    } else {
      activateRight();
    }
  });

  // Clic sur la colonne droite pour l'activer
  colRight.on('click', function() {
    if (colRight.hasClass('inactive')) {
      presetSelect.val('');
      activateRight();
    }
  });

  // Port "Autre"
  $('#404-right-port').on('change', function() {
    const wrap = $('#404-port-custom-wrap');
    const custom = $('#404-right-port-custom');
    if ($(this).val() === '0') {
      wrap.show();
      custom.prop('disabled', false).focus();
    } else {
      wrap.hide();
      custom.prop('disabled', true);
    }
  });

  function activateLeft(key) {
    const preset = a404Presets[key];

    // Remplir les champs cachés
    $('#404-left-host').val(preset.host);
    $('#404-left-port').val(preset.port);
    $('#404-left-encryption').val(preset.encryption);

    // Afficher l'info du preset
    if (preset.info) {
      presetInfo.html(preset.info).show();
    }

    // Activer colonne gauche
    colLeft.removeClass('inactive').addClass('active');
    colLeft.find('input[type=email], input[type=password], select').prop('disabled', false);

    // Désactiver colonne droite
    colRight.removeClass('active').addClass('inactive');
    colRight.find('input, select').prop('disabled', true);
  }

  function activateRight() {
    presetInfo.hide();

    // Désactiver colonne gauche
    colLeft.removeClass('active').addClass('inactive');
    colLeft.find('input[type=email], input[type=password], select').prop('disabled', true);

    // Activer colonne droite
    colRight.removeClass('inactive').addClass('active');
    colRight.find('input, select').prop('disabled', false);

    // Gérer le port "Autre"
    const portSelect = $('#404-right-port');
    if (portSelect.val() === '0') {
      $('#404-port-custom-wrap').show();
      $('#404-right-port-custom').prop('disabled', false);
    }
  }

  // État initial : si un preset est déjà sélectionné, l'activer
  if (presetSelect.val()) {
    activateLeft(presetSelect.val());
  }
});
