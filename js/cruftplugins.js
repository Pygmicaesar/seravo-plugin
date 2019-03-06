'use strict';




jQuery(document).ready(function($) {
  function seravo_ajax_delete_plugin(plugin_name, callback) {
    $.post(
      seravo_cruftplugins_loc.ajaxurl,
      { type: 'POST',
        'action': 'seravo_remove_plugins',
        'removeplugin': plugin_name,
        'nonce': seravo_cruftplugins_loc.ajax_nonce, },
      function( rawData ) {
        var data = JSON.parse(rawData);
        if ( data[ data.length - 1 ].indexOf('Success: Deleted 1 of 1 plugins.') != -1 ) {
          callback();
        } else {
          confirm(seravo_cruftplugins_loc.failure);
        }
      });
  }

  // Generic ajax report loader function
  function seravo_plugin_load_report(section) {
    $.post(
      seravo_cruftplugins_loc.ajaxurl,
      { 'action': 'seravo_list_cruft_plugins',
        'section': section,
        'nonce': seravo_cruftplugins_loc.ajax_nonce, },
      function(rawData) {
        if (rawData.length == 0) {
          $('#' + section).html(seravo_cruftplugins_loc.no_data);
        }
        $('#' + section + '_loading').fadeOut();
        var $bodyElement = $('#cruftplugins_status');
        // Parse data
        var data = JSON.parse(rawData);
        var sortedData = sort_plugin_data_array(data);
        // Show message
        if ( sortedData.length != 0 ) {
          $( '#cruftplugins_status' ).prepend('<p>' + seravo_cruftplugins_loc.cruftplugins + '</p>');
        } else {
          $( '#cruftplugins_status' ).prepend('<b>' + seravo_cruftplugins_loc.no_cruftplugins + '</b>');
        }
        // Create tables
        $.each(Object.keys(sortedData), function(i, status) {
          // Create table body
          appendTable($bodyElement, status)
          // Insert entries
          var $entries = $('#cruftplugins_' + status + ' .cruftplugins_entries');
          $.each(sortedData[status], function (o, plugin) {
            var html = '<tr class="cruftplugin">' +
              '<td class="cruftplugin-delete"><input data-plugin-name="' + plugin.name  + '" class="cruftplugin-check" type="checkbox"></td>' +
              '<td class="cruftplugin-path">' + plugin.name  + '</td>' +
            '</tr>';
          $entries.append(html);
          })
          if ( $('#cruftplugins_' + status).find('.cruftplugins_entries').children().length >= 30) {
            $('#cruftplugins_' + status).find( '.cruftplugins_less-than' ).show(400);
          }
        })
        // Remove files
        $('.cruftplugin-delete-button').click(function(event) {
          event.preventDefault();
          var is_user_sure = confirm(seravo_cruftplugins_loc.confirm);
          if ( ! is_user_sure ) {
            return;
          }
          var $this = $(this);
          var status = $this.data('status');
          var $body = $('#cruftplugins_' + status);
          var cruft_list = [];
          var remove_rows = [];
          // Get selected checkboxes
          $body.find('.cruftplugin-check').each(function(){
            var $cthis = $(this);
            if ( $cthis.is(":checked") ) {
              remove_rows.push( $cthis.parents(':eq(1)') );
              cruft_list.push( $cthis.attr('data-plugin-name') );
            }
          });
          // Delete plugins
          if ( cruft_list.length > 0 ) {
            seravo_ajax_delete_plugin(cruft_list, function() {
              remove_rows.forEach(function( row ) {
                row.animate({
                  opacity: 0
                }, 600, function() {
                  row.remove();
                  if ( $body.find('.cruftplugins_entries').children().length >= 30 ) {
                    $body.find( '.cruftplugins_less-than' ).show(400);
                  }
                  if ( $body.find('.cruftplugins_entries').children().length == 0 ) {
                    $body.children().remove();
                    $body.append('<b>' + seravo_cruftplugins_loc.no_cruftplugins + '</b>');
                  }
                });
              });
            });
          }
        });
      }
    ).fail(function() {
      $('#' + section + '_loading').html(seravo_cruftplugins_loc.fail);
    });
  }
  function sort_plugin_data_array (dataArray) {
    var resultArray = {};
    $.each(dataArray, function (i, plugin) {
      // If no plugin key is created create one
      if (Object.keys(resultArray).indexOf(plugin.status) === -1) {
        resultArray[plugin.status] = [];
      }
      // Push plugin to array
      resultArray[plugin.status].push(plugin);
    });
    return resultArray;
  }

  function appendTable ($element, status) {
    var html = 
    '<b>' + seravo_cruftplugins_loc[status] + '</b><p>' + seravo_cruftplugins_loc[status + '_desc'] + '</p>'+
    '<table id="cruftplugins_' + status + '">' +
      '<thead>' +
        '<tr>' +
          '<td><input class="cruftplugin-select-all" type="checkbox" data-enpassusermodified="yes"></td>' +
          '<td class="cruft-tool-selector"><b>Select all files</b></td>' +
        '</tr>' +
      '</thead>' +
      '<tbody class="cruftplugins_entries">' +
      '</tbody>' +
      '<tfoot class="cruftplugins_less-than" style="display: none;">' +
        '<tr>' +
          '<td><input class="cruftplugin-select-all" type="checkbox"></td>' +
          '<td class="cruft-tool-selector"><b>Select all files</b></td>' +
        '</tr>' +
      '</tfoot>' +
    '</table>' +
    '<button class="cruftplugin-delete-button" data-status="' + status + '" type="button">Delete</button>';
    $element.append(html)
  }

  // Load on page load
  seravo_plugin_load_report('cruftplugins_status');
});
