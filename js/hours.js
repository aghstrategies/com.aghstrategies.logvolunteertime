CRM.$(function ($) {
  var setUpNeedSelect = function () {
      $('#needSelectId').find('option').remove();
      var $volProjectId = $('#volunteer_project_select').val();
      $('#needSelectId').crmEntityRef({
        entity: 'VolunteerNeed',
        api: {
          params: { project_id: $volProjectId },
          description_field: ['display_time', 'role_description'],
          label_field: 'role_label',
        },

        select: {
          // no searchbox
          // minimumResultsForSearch: -1,
          // infinite scrolling
          minimumInputLength: 0,
          placeholder: '- Select a Volunteer Need -',
        },
      });
    };

  var txt1 = '<div class = "crm-section"><div class="label"><label for="volunteer_need_select">Volunteer Need <span class = "crm-marker" title="This Field is required.">*</span></label></div><div class="content jsselect"></div><div class="clear"></div><div>';
  $('div.crm-section:nth-child(6)').append(txt1);
  var $needSelect = $('<input type=\'hidden\' name= \'projectSelect\' id=\'needSelectId\' class=\'crm-form-select\' />');
  $needSelect.appendTo('.jsselect');
  setUpNeedSelect();
  $('#volunteer_project_select').change(setUpNeedSelect);
  $('#needSelectId').change(function () {
      var $volNeedId = $('#needSelectId').val();
      console.log($volNeedId);
      $("input[name='volunteer_need_text']").val($volNeedId);
    });
});
