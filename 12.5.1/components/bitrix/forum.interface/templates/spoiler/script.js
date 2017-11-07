;(function(window){
if (window.ForumInitSpoiler) return;
top.ForumInitSpoiler = window.ForumInitSpoiler = function(oHead)
{
	if (typeof oHead != "object" || !oHead)
		return false; 
	var oBody = oHead.nextSibling;
	oBody.style.display = (oBody.style.display == 'none' ? '' : 'none'); 
	oHead.className = (oBody.style.display == 'none' ? '' : 'forum-spoiler-head-open'); 
}
})(window);