$(document).ready(function() {
  var lfnds_changeSum = new elefundsSum();

  //Move donation fields into the form
  $('#elefunds_donation_cent').insertBefore('#sAGB');
  $('#elefunds_suggested_round_up_cent').insertBefore('#sAGB');

  //Add field for the selected receivers to the form
  $('<input type="hidden" id="elefunds_receivers" name="elefunds_receivers" value="" />').insertBefore('#sAGB');
  function updateReceivers() {
    var receivers = $('input[name="elefunds_receiver[]"]:checked').map(function() {
      return $(this).val();
    }).get().join();

    //Update the receivers field
    $('#elefunds_receivers').val(receivers);
  }

  //Copy the elefunds agree field into the form
  $('<input type="hidden" id="elefunds_agree" name="elefunds_agree" value="false" />').insertBefore('#sAGB');
  $('#elefunds_checkbox').on('change', function() {
    $('#elefunds_agree').val($(this).prop('checked'));
    updateReceivers();
  });

  //Copy the elefunds receipt field into the form
  $('<input type="hidden" id="elefunds_receipt_input" name="elefunds_receipt_input" value="false" />').insertBefore('#sAGB');
  $('#elefunds_receipt_checkbox').on('change', function() {
    $('#elefunds_receipt_input').val($(this).prop('checked'));
  });

  $('[name="elefunds_receiver[]"]').on('change', function() {
    updateReceivers();
  });

});

var elefundsSum = function() {
  this.$totalAmountNode = $('#aggregation .totalamount > p > strong');
  this.originalSum = this.$totalAmountNode.html();

  this.roundSum = $('#elefunds').data('elefunds-roundSum');

  //Include the elefunds donation before the total amount
  this.$donationLabel_pre = $('#aggregation_left').find('.border > p:first').first();
  this.$donationLabel = this.$donationLabel_pre.clone();
  this.$donationLabel.find('span:first').html('Spende');
  this.$donationLabel_pre.parent().append(this.$donationLabel.hide());

  this.$donationSum_pre = $('#aggregation').find('.border > p:first').first();
  this.$donationSum = this.$donationSum_pre.clone();
  this.$donationSum_pre.parent().append(this.$donationSum.hide());

  this.addEvents();
};

elefundsSum.prototype.addEvents = function() {
  var that = this;
  var $elefunds = $('#elefunds');

  $elefunds.on('elefunds_enabled', function() {
    var floatValue = $elefunds.data('elefunds-donation').donationFloat;

    that.$donationSum.find('strong:first').html(floatValue + ' ' + window.elefunds.options.currency);
    that.$donationSum.slideDown();
    that.$donationLabel.slideDown();

    that.updateSum();
  });

  $elefunds.on('elefunds_disabled', function() {
    that.$totalAmountNode.fadeOut(function() {
      $(this).html(that.originalSum);
      $(this).fadeIn('fast');
    });

    that.$donationLabel.slideUp();
    that.$donationSum.slideUp();
  });

  $elefunds.on('elefunds_donationChange', function() {
    that.updateSum();
  });
};

elefundsSum.prototype.updateSum = function() {
  var floatValue = $('#elefunds').data('elefunds-donation').donationFloat;
  this.$donationSum.find('strong:first').html(floatValue + ' ' + window.elefunds.options.currency);

  this.$totalAmountNode.fadeOut(function() {
    $(this).html($('#elefunds').data('elefunds-roundSum') + ' ' + window.elefunds.options.currency);
    $(this).fadeIn('fast');
  });
};
