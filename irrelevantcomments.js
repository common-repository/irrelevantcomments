$j=jQuery.noConflict();
function irrComments_toggle(id) {
  if ($j("#irrComment"+id).css("display") == "none") {
    $j("#irrComment"+id).fadeIn("normal");
    $j("#irrCommentLink"+id).fadeTo("fast", 0.5);
    $j("#irrCommentLink"+id+" a").text(irrcomments_lang.hide);
  }
  else {
    $j("#irrComment"+id).fadeOut("fast");
    $j("#irrCommentLink"+id).fadeTo("fast", 1.0);
    $j("#irrCommentLink"+id+" a").text(irrcomments_lang.show);
  } 
}
