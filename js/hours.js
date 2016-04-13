CRM.$(function ($) {
  var txt1 = '<div class = "crm-section"><div class="label"><label for="volunteer_need_select">Volunteer Need</label></div><div class="content jsselect"></div><div class="clear"></div><div>';
  $('div.crm-section:nth-child(6)').append(txt1);

  var needSelect = $("<select id=\"needSelectId\" class=\"crm-form-select\" />");
  needSelect.appendTo('.jsselect');
  $('#volunteer_project_select').change(function () {
    $('#needSelectId').find('option').remove();
    console.log('Handler for .change() called.');
    var $volProjectId = $('#volunteer_project_select').val();
    CRM.api3('VolunteerNeed', 'get', {
      'project_id': $volProjectId,
    }).done(function (result) {
        var needs = result.values;
        $.each(needs, function () {
          $('<option />', { value: this.id, text: this.role_label }).appendTo(needSelect);
        });

      });

  });

  $('#needSelectId').change(function () {
      var $volNeedId = $('#needSelectId').val();
      console.log($volNeedId);
      $("input[name='volunteer_need_text']").val($volNeedId);
    });
});
