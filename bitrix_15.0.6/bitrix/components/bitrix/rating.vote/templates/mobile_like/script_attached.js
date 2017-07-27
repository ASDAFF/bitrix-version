if (!BXRL)
{
	var BXRL = {};
	var BXRLW = null;
}

RatingLike = function(likeId, entityTypeId, entityId, available)
{	
	this.enabled = true;
	this.likeId = likeId;
	this.entityTypeId = entityTypeId;
	this.entityId = entityId;
	this.available = available == 'Y'? true: false;

	this.box = BX('bx-ilike-box-'+likeId);
	if (this.box === null)
	{
		this.enabled = false;
		return false;
	}

	this.button = BX('bx-ilike-button-'+likeId);
	if (!this.button)
	{
		this.button = BX('rating_button');
	}

	this.count = BX.findChild(this.button, { tagName: 'div', className: 'post-item-inform-right' }, true, false);
	this.countText = BX.findChild(this.box, {tagName:'span', className:'post-item-inform-right-text'}, true, false);
	this.buttonCountText = BX.findChild(this.button, {tagName:'span', className:'post-item-inform-right-text'}, true, false);
	this.likeTimeout = false;
	this.lastVote = BX.hasClass(this.button, 'post-item-inform-likes-active') ? 'plus' : 'cancel';
}

RatingLike.Set = function(likeId, entityTypeId, entityId, available)
{
	BXRL[likeId] = new RatingLike(likeId, entityTypeId, entityId, available);
	if (BXRL[likeId].enabled)
	{
		RatingLike.Init(likeId);
	}
};

RatingLike.Init = function(likeId)
{
	// like/unlike button
	if (BXRL[likeId].available)
	{
		BX.unbindAll(BXRL[likeId].button);
		BX.bind(BXRL[likeId].button, 'click', function(e) 
		{
			clearTimeout(BXRL[likeId].likeTimeout);

			if (BX.hasClass(BXRL[likeId].button, 'post-item-inform-likes-active'))
			{
				var newValue = parseInt(BXRL[likeId].countText.innerHTML) - 1;
				BXRL[likeId].countText.innerHTML = newValue;
				if (BXRL[likeId].buttonCountText)
				{
					BXRL[likeId].buttonCountText.innerHTML = newValue;
				}
				BX.removeClass(BXRL[likeId].button, 'post-item-inform-likes-active');

				if (parseInt(newValue) <= 0)
				{
					if (
						BX('rating-footer-wrap')
						&& !BX('lenta_notifier') // not in lenta
					)
					{
						BX('rating-footer-wrap').style.display = 'none';
					}
				}
				else
				{
					if (BX('bx-ilike-list-others'))
					{
						BX('bx-ilike-list-others').style.display = "block";
					}
					if (BX('bx-ilike-list-youothers'))
					{
						BX('bx-ilike-list-youothers').style.display = "none";
					}
				}

				BXRL[likeId].likeTimeout = setTimeout(function(){
					if (BXRL[likeId].lastVote != 'cancel')
						RatingLike.Vote(likeId, 'cancel');
				}, 1000);
			}
			else
			{
				var newValue = parseInt(BXRL[likeId].countText.innerHTML) + 1;
				BXRL[likeId].countText.innerHTML = newValue;
				if (BXRL[likeId].buttonCountText)
				{
					BXRL[likeId].buttonCountText.innerHTML = newValue;
				}
				BX.addClass(BXRL[likeId].button, 'post-item-inform-likes-active');

				var blockCounter = false;

				if (
					BX('rating-footer-wrap')
					&& !BX('lenta_notifier') // not in lenta
				)
				{
					BX('rating-footer-wrap').style.display = 'block';
				}

				if (parseInt(newValue) != 1)
				{
					if (BX('bx-ilike-list-others'))
					{
						BX('bx-ilike-list-others').style.display = "none";
					}
					if (BX('bx-ilike-list-youothers'))
					{
						BX('bx-ilike-list-youothers').style.display = "block";
					}
				}

				BXRL[likeId].likeTimeout = setTimeout(function(){
					if (BXRL[likeId].lastVote != 'plus')
					{
						RatingLike.Vote(likeId, 'plus');
					}
				}, 1000);
			}
			BX.PreventDefault(e);
		});
		
	}
}

RatingLike.Vote = function(likeId, voteAction)
{
	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'POST',
		'url': '/mobile/ajax.php?mobile_action=like',
		'data': {
			'RATING_VOTE': 'Y', 
			'RATING_VOTE_TYPE_ID': BXRL[likeId].entityTypeId, 
			'RATING_VOTE_ENTITY_ID': BXRL[likeId].entityId, 
			'RATING_VOTE_ACTION': voteAction,
			'sessid': BX.message('RVSessID')
		},
		'callback': function(data) 
		{
			if (
				typeof data != 'undefined'
				&& typeof data.action != 'undefined'
				&& typeof data.items_all != 'undefined'
			)
			{
				BXRL[likeId].lastVote = data.action;
				BXRL[likeId].countText.innerHTML = data.items_all;
				if (BXRL[likeId].buttonCountText)
				{
					BXRL[likeId].buttonCountText.innerHTML = data.items_all;
				}

				var oldValue = BXRL[likeId].box.parentNode.getAttribute('data-counter');
				if (oldValue !== null)
				{
					oldValue = parseInt(oldValue);
					BXRL[likeId].box.parentNode.setAttribute('data-counter', ((voteAction == 'plus') ? (oldValue + 1) : (oldValue - 1)));
				}

				if (
					typeof (oMSL) != 'undefined'
					&& typeof (oMSL.logId) != 'undefined'
					&& parseInt(oMSL.logId) > 0
				)
				{
					app.onCustomEvent('onLogEntryRatingLike', {
						rating_id: likeId,
						voteAction: voteAction,
						logId: oMSL.logId
					});
				}
			}
			else
			{
				var newValue = 0;
				if (voteAction == 'plus')
				{
					newValue = parseInt(BXRL[likeId].countText.innerHTML) - 1;
					BX.removeClass(BXRL[likeId].button, 'post-item-inform-likes-active');
				}
				else
				{
					newValue = parseInt(BXRL[likeId].countText.innerHTML) + 1;
					BX.addClass(BXRL[likeId].button, 'post-item-inform-likes-active');
				}
				BXRL[likeId].countText.innerHTML = newValue;
				if (BXRL[likeId].buttonCountText)
				{
					BXRL[likeId].buttonCountText.innerHTML = newValue;
				}
			}
		},
		'callback_failure': function(data)
		{
			var newValue = 0;
			if (voteAction == 'plus')
			{
				newValue = parseInt(BXRL[likeId].countText.innerHTML) - 1;
				BX.removeClass(BXRL[likeId].button, 'post-item-inform-likes-active');
			}
			else
			{
				newValue = parseInt(BXRL[likeId].countText.innerHTML) + 1;
				BX.addClass(BXRL[likeId].button, 'post-item-inform-likes-active');
			}
			BXRL[likeId].countText.innerHTML = newValue;
			if (BXRL[likeId].buttonCountText)
			{
				BXRL[likeId].buttonCountText.innerHTML = newValue;
			}
		}
	});
	return false;
}

RatingLike.List = function(likeId)
{
	if (app.enableInVersion(2))
	{
		app.openTable({
			callback: function() {},
			url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/index.php?mobile_action=get_likes&RATING_VOTE_TYPE_ID=' + BXRL[likeId].entityTypeId + '&RATING_VOTE_ENTITY_ID=' + BXRL[likeId].entityId + '&URL=' + BX.message('RVPathToUserProfile'),
			markmode: false,
			showtitle: false,
			modal: false,
			cache: false,
			outsection: false,
			cancelname: BX.message('RVListBack')
		});
	}

	return false;
}