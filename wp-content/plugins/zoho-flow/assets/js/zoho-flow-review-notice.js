

jQuery(document).ready(function(){

  var reviewNoticeNonce = '';
  if (typeof zohoFlowReviewNotice !== 'undefined' && zohoFlowReviewNotice.nonce) {
    reviewNoticeNonce = zohoFlowReviewNotice.nonce;
  }

  jQuery( '#notice-review-botton' ).click( function() { //NO I18N
    var data = {};
    data.action = "zoho_flow_change_next_review_date";
    data.days_to_increase = 90;
    data.review_notice_nonce = reviewNoticeNonce;
    jQuery.post( ajaxurl, data, function(response){
		} );
		jQuery("#flow-review-notice").remove(); //NO I18N
	});

  jQuery( '#notice-donot-botton' ).click( function() { //NO I18N
    var data = {};
    data.action = "zoho_flow_change_next_review_date";
    data.days_to_increase = 365;
    data.review_notice_nonce = reviewNoticeNonce;
    jQuery.post( ajaxurl, data, function(response){
		} );
		jQuery("#flow-review-notice").remove(); //NO I18N
	});

  jQuery( '#notice-later-botton' ).click( function() { //NO I18N
    var data = {};
    data.action = "zoho_flow_change_next_review_date";
    data.days_to_increase = 15;
    data.review_notice_nonce = reviewNoticeNonce;
    jQuery.post( ajaxurl, data, function(response){
		} ); 
		jQuery("#flow-review-notice").remove(); //NO I18N
	});
});
