'use strict';

jQuery(document).ready(function ($) {
  var section = 'cruftthemes_status';
  var $body = $('#' + section);

  function appendTable() {
    var html =
      '<table id="' + section  + '">' +
      '<thead>' +
      '<tr>' +
      '<td><input class="' + section  + '_select-all" type="checkbox"></td>' +
      '<td class="cruft-tool-selector cruft-themes-td"><b>Select all files</b></td>' +
      '</tr>' +
      '</thead>' +
      '<tbody class="' + section  + '_entries">' +
      '</tbody>' +
      '<tfoot class="' + section  + '_less-than" style="display: none;">' +
      '<tr>' +
      '<td><input class="' + section  + '_select-all" type="checkbox"></td>' +
      '<td class="cruft-tool-selector"><b>Select all files</b></td>' +
      '</tr>' +
      '</tfoot>' +
      '</table>' +
      '<button class="' + section  + '_delete" type="button">Delete</button>';
    $body.append(html)
    return $('.' + section + '_entries');
  }

  function appendLine($element, name) {
    var html =
    '<tr class="crufttheme">' +
    '<td class="crufttheme-delete">' +
    '<input data-plugin-name="' + name + '" class="crufttheme-check" type="checkbox">' +
    '</td>' +
    '<td class="crufttheme-path">' + name + '</td>' +
    '</tr>';
    $element.append(html)
  }


  $.post(seravo_cruftthemes_loc.ajaxurl, {
    'action': 'seravo_list_cruft_themes',
    'section': section,
    'nonce': seravo_cruftthemes_loc.ajax_nonce
  }, function(rawData) {
    console.log(rawData)
      if (rawData.length == 0) {
        $('#' + section).html(seravo_cruftthemes_loc.no_data);
      }
      $('#' + section + '_loading').fadeOut();
      var data = JSON.parse(rawData);
      // Create table
      // Parse data (Parents, and Childers) create data array from where you can create useaful table
      var $entries = appendTable();
      data.forEach(function (theme) {
        appendLine($entries, theme.name)
      })
  }).fail(function() {
    $('#' + section + '_loading').html(seravo_cruftthemes_loc.fail);
  });
});
