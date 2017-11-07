function ValidateForm(form, ajax_type)
{
	if (typeof form != "object" || typeof form.POST_MESSAGE != "object")
		return false;
		
	var errors = "";
	var MessageMax = 64000;
	var MessageLength = form.POST_MESSAGE.value.length;

	if (form.POST_SUBJ && (form.POST_SUBJ.value.length < 2))
		errors += oErrors['no_topic_name'];

	if (MessageLength < 2)
		errors += oErrors['no_message'];
	else if ((MessageMax != 0) && (MessageLength > MessageMax))
		errors += oErrors['max_len'].replace("#MAX_LENGTH#", MessageMax).replace("#LENGTH#", MessageLength);

	if (errors != "")
	{
		alert(errors);
		return false;
	}
	
	var arr = form.getElementsByTagName("input");
	for (var i=0; i < arr.length; i++)
	{
		var butt = arr[i];
		if (butt.getAttribute("type") == "submit")
			butt.disabled = true;
	}
	return true;
}
