var waitTimeout = null;
var waitDiv = null;
var	waitPopup = null;
var waitTime = 500;
var arrGetComments = new Array();

function __logShowCommentForm(log_id, error, comment)
{
	var pForm = BX('sonet_log_comment_form_container');
	var commentLink = false;
	var place = false;
	var commentsNode = false;
	var tmpPos = 0;
	var iMaxHeight = 0;
	var commentLinkOffsetHeight;

	place = pForm.parentNode;
	if (BX(place))
	{
		if (BX(place).id == 'sonet_log_comment_form_place_' + log_id && pForm.style.display != 'none')
			return false;

		if (pForm.style.display == 'none')
			pForm.style.display = 'block';

		if (BX.hasClass(place, 'sonet-log-comment-form-place'))
		{
			var oldCommentsNode = BX.findParent(place, {'className': 'feed-comments-block'});
			if (BX(oldCommentsNode))
			{
				var oldCommentsNodeLimited = BX.findChild(oldCommentsNode, {'tag': 'div', 'className': 'feed-comments-limited'}, false);
				var oldCommentsNodeFull = BX.findChild(oldCommentsNode, {'tag': 'div', 'className': 'feed-comments-full'}, false);
				if (!BX(oldCommentsNodeLimited) && !BX(oldCommentsNodeFull))
					oldCommentsNode.style.display = 'none';

				var oldCommentsFooter = BX.findPreviousSibling(place, {'tag': 'div', 'className': 'feed-com-footer'});
				if (BX(oldCommentsFooter))
					oldCommentsFooter.style.display = 'block';
			}
		}
	}

	BX('sonet_log_comment_form_place_' + log_id).appendChild(pForm); // Move form
	pForm.style.display = "block";

	CommentFormWidth = BX('sonet_log_comment_text', true).offsetWidth;
	CommentFormColsDefault = Math.floor(CommentFormWidth / CommentFormSymbolWidth);
	CommentFormRowsDefault = BX('sonet_log_comment_text', true).rows;

	var commentsBlock = BX('feed_comments_block_' + log_id);
	if (commentsBlock)
	{
		var source_node = BX.findChild(commentsBlock, {'tag': 'div', 'className': 'feed-com-footer'}, false);
		if (BX(source_node))
			BX(source_node).style.display = 'none';
	}

	BX.focus(BX('sonet_log_comment_text'));

	BX('sonet_log_comment_logid').value = log_id;
	BX('sonet_log_comment_form').action.value = 'add_comment';

	if(error == "Y")
	{
		if(comment && comment.length > 0)
		{
			comment = comment.replace(/\/</gi, '<');
			comment = comment.replace(/\/>/gi, '>');
			BX('sonet_log_comment_text').value = comment;
		}
	}

	return false;
}


function __logCommentAdd()
{
	var textarea = BX('sonet_log_comment_text');
	var logIDField = BX('sonet_log_comment_logid');
	var log_id = logIDField.value
	var sonetLXmlHttpSet3 = new XMLHttpRequest();

	if (!textarea.value)
		return;

	sonetLXmlHttpSet3.open("POST", BX.message('sonetLESetPath'), true);
	sonetLXmlHttpSet3.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

	sonetLXmlHttpSet3.onreadystatechange = function()
	{
		if(sonetLXmlHttpSet3.readyState == 4)
		{
			__logCommentCloseWait();
			if(sonetLXmlHttpSet3.status == 200)
			{
				var data = LBlock.DataParser(sonetLXmlHttpSet3.responseText);
				if (typeof(data) == "object")
				{
					if (data[0] == '*')
					{
						if (sonetLErrorDiv != null)
						{
							sonetLErrorDiv.style.display = "block";
							sonetLErrorDiv.innerHTML = sonetLXmlHttpSet3.responseText;
						}
						return;
					}
					sonetLXmlHttpSet3.abort();

					var commentID = false;
					var strMessage = '';

					if (data["commentID"] != 'undefined' && data["commentID"] > 0)
						commentID = data["commentID"];
					else if (data["strMessage"] != 'undefined' && data["strMessage"].length > 0)
					{
						strMessage = data["strMessage"];
						__logShowCommentForm(log_id, "Y", data["commentText"]);
					}

					__logCommentGet(log_id, commentID, strMessage);
					__logCommentFormAutogrow(textarea);

					if (BX("log_entry_follow_" + log_id, true))
					{
						var strFollowOld = (BX("log_entry_follow_" + log_id, true).getAttribute("data-follow") == "Y" ? "Y" : "N");
						if (strFollowOld == "N")
						{
							BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetLFollowY');
							BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", "Y");
						}
					}
				}
			}
			else
			{
				// error!
			}
		}
	}

	sonetLXmlHttpSet3.send("r=" + Math.floor(Math.random() * 1000)
		+ "&" + BX.message('sonetLSessid')
		+ "&log_id=" + encodeURIComponent(log_id)
		+ "&p_smile=" + encodeURIComponent(BX.message('sonetLPathToSmile'))
		+ "&p_ubp=" + encodeURIComponent(BX.message('sonetLPathToUserBlogPost'))
		+ "&p_gbp=" + encodeURIComponent(BX.message('sonetLPathToGroupBlogPost'))
		+ "&p_umbp=" + encodeURIComponent(BX.message('sonetLPathToUserMicroblogPost'))
		+ "&p_gmbp=" + encodeURIComponent(BX.message('sonetLPathToGroupMicroblogPost'))
		+ "&f_id=" + encodeURIComponent(BX.message('sonetLForumID'))
		+ "&bapc=" + encodeURIComponent(BX.message('sonetLBlogAllowPostCode'))
		+ "&site=" + encodeURIComponent(BX.message('sonetLSiteId'))
		+ "&lang=" + encodeURIComponent(BX.message('sonetLLangId'))
		+ "&message=" + encodeURIComponent(textarea.value)
		+ "&action=add_comment"
	);
	textarea.value = '';
	__logCommentShowWait(500, log_id);
}

function __logCommentGet(logID, commentID, strMessage)
{
	var container = BX('feed_comments_block_' + logID);

	if (container && container != null)
	{
		if (commentID > 0)
		{
			var sonetLXmlHttpGet3 = new XMLHttpRequest();

			var params = "action=get_comment"
				+ "&site=" + BX.util.urlencode(BX.message('sonetLSiteId'))
				+ "&lang=" + BX.util.urlencode(BX.message('sonetLLangId'))
				+ "&nt=" + BX.util.urlencode(BX.message('sonetLNameTemplate'))
				+ "&dtf=" + BX.util.urlencode(BX.message('sonetLDateTimeFormat'))
				+ "&sl=" + BX.util.urlencode(BX.message('sonetLShowLogin'))
				+ "&as=" + BX.util.urlencode(BX.message('sonetLAvatarSizeComment'))
				+ "&p_user=" + BX.util.urlencode(BX.message('sonetLPathToUser'))
				+ "&p_smile=" + BX.util.urlencode(BX.message('sonetLPathToSmile'))
				+ "&cid=" + BX.util.urlencode(commentID);

			sonetLXmlHttpGet3.open(
				"get",
				BX.message('sonetLEGetPath') + "?" + BX.message('sonetLSessid')
					+ "&" + params
					+ "&r=" + Math.floor(Math.random() * 1000)
			);
			sonetLXmlHttpGet3.send(null);

			sonetLXmlHttpGet3.onreadystatechange = function()
			{
				if(sonetLXmlHttpGet3.readyState == 4)
				{
					if(sonetLXmlHttpGet3.status == 200)
					{
						var data = LBlock.DataParser(sonetLXmlHttpGet3.responseText);
						if (typeof(data) == "object")
						{
							if (data[0] == '*')
							{
								if (sonetLErrorDiv != null)
								{
									sonetLErrorDiv.style.display = "block";
									sonetLErrorDiv.innerHTML = sonetLXmlHttpGet3.responseText;
								}
								return;
							}
							sonetLXmlHttpGet3.abort();
							var arComment = data["arComment"];
							var arCommentFormatted = data["arCommentFormatted"];
							__logCommentShow(arCommentFormatted, container);

						}
					}
					else
					{
						// error!

					}
				}
			}
		}
		else if (strMessage.length > 0)
			__logMessageShow(strMessage, container);
	}
}

function __logCommentShow(arComment, container)
{
	anchor_id = Math.floor(Math.random()*100000) + 1;
	avatar = false;
	containerHidden = null;

	if (container)
	{
		var commentsFull = BX.findChild(container, {'tag': 'div', 'className': 'feed-comments-full'}, false);
		var commentsFullInner = BX.findChild(commentsFull, {'tag': 'div', 'className': 'feed-comments-full-inner'}, false);
		var commentsLimited = BX.findChild(container, {'tag': 'div', 'className': 'feed-comments-limited'}, false);

		if (commentsFull && commentsFull.style.display != 'none')
		{
			container = BX.findChild(commentsFull, {'tag': 'div', 'className': 'feed-comments-full-inner'} );
			containerHidden = BX.findChild(commentsLimited, {'tag': 'div', 'className': 'feed-comments-limited-inner'} );
		}
		else if (commentsLimited)
		{
			container = BX.findChild(commentsLimited, {'tag': 'div', 'className': 'feed-comments-limited-inner'} );
			if (commentsFullInner.innerHTML.length > 0)
				containerHidden = BX.findChild(commentsFull, {'tag': 'div', 'className': 'feed-comments-full-inner'} );
		}
		else
		{
			commentsFull = container.insertBefore(BX.create('div', { props: { 'className': 'feed-comments-full' } } ), BX.findChild(container, { 'tag': 'div', 'className': 'feed-com-footer' } ));
			container = commentsFull.appendChild(BX.create('div', { props: { 'className': 'feed-comments-full-inner' } } ));
		}

		if (arComment["AVATAR_SRC"] && arComment["AVATAR_SRC"] != 'undefined')
			avatar = BX.create('div', { props: { 'className': 'feed-com-avatar' }, style: { background: "url('" + arComment["AVATAR_SRC"] + "') no-repeat center #FFFFFF" } } );
		else
			avatar = BX.create('div', { props: { 'className': 'feed-com-avatar' } } );

		var newCommentNode = BX.create('div', {
			props: { 'className': 'feed-com-block' },
			children: [
				avatar,
				BX.create('span', {
					props: { 'className': 'feed-com-name' },
					children: [
						BX.create('a', {
							props: { 'id': 'anchor_' + anchor_id },
							attrs: { 'href': arComment["CREATED_BY"]["URL"] },
							html: arComment["CREATED_BY"]["FORMATTED"]
						})
					]
				}),
				BX.create('div', {
					props: { 'className': 'feed-com-informers' },
					children: [
						BX.create('span', { props: { 'className': 'feed-time' }, html: arComment["LOG_TIME_FORMAT"] } )
					]
				}),
				BX.create('div', {
					props: { 'className': 'feed-com-text-wrap' },
					children: [
						BX.create('div', {
							props: { 'className': 'feed-com-text' },
							children: [
								BX.create('div', {
									props: { 'className': 'feed-com-text-inner' },
									children: [
										BX.create('div', {
											props: { 'className': 'feed-com-text-inner-inner' },
											children: [
												BX.create('span', { html: arComment["MESSAGE_FORMAT"] })
											]
										})
									]
								})
							]
						})
					]
				})
			]
		});

		container.appendChild(newCommentNode);

		//adding comment copy to have comments both in limited and full modes
		if (containerHidden !== null)
		{
			newCommentCopy = BX.clone(newCommentNode);
			containerHidden.appendChild(newCommentCopy);
			containerHidden.style.display = 'none';
		}

		BX.tooltip(arComment["USER_ID"], "anchor_" + anchor_id, "");

		commentsNode = BX.findParent(container, {'className': 'sonet-log-item-comments'});
		if (BX(commentsNode) && BX(commentsNode).style.maxHeight != '')
		{
			tmpPos = BX(commentsNode).style.maxHeight.indexOf('px');
			iMaxHeight = parseInt(BX(commentsNode).style.maxHeight.substr(0, tmpPos));
			BX(commentsNode).style.maxHeight = (iMaxHeight + newCommentNode.offsetHeight) + 'px';
		}

	}
}

function __logMessageShow(strMessage, container)
{
	if (container)
	{
		var commentsFull = BX.findChild(container, {'tag': 'span', 'className': 'feed-comments-full-inner'}, false);
		var commentsLimited = BX.findChild(container, {'tag': 'span', 'className': 'feed-comments-limited-inner'}, false);

		if (commentsFull && commentsFull.style.display != 'none')
			container = commentsFull;
		else if (commentsLimited && commentsLimited.style.display != 'none')
			container = commentsLimited;
		else
		{
			commentsFull = container.appendChild(BX.create('span', {
					props: {
						'className': 'feed-com-block'
					}
				})
			);
			container = commentsFull;
		}

		if (container)
			container.appendChild(BX.create('div', {
				props: {
				},
				html: strMessage
			})
			);
	}
}

function __logComments(logID, ts, bFollow)
{
	bFollow = !!bFollow;
	var container = BX('feed_comments_block_' + logID);
	var contentBlock = false;
	var expandBlock = false;
	var comment_message = '';
	var comment_datetime = '';
	var avatar = false;
	var class_name_unread = '';
	var you_like_class = "";
	var you_like_text = "";
	var vote_text = null;

	if (container && container != null)
	{
		var commentsFull = BX.findChild(container, {'tag': 'div', 'className': 'feed-comments-full'}, false);
		var commentsLimited = BX.findChild(container, {'tag': 'div', 'className': 'feed-comments-limited'}, false);

		if (commentsFull && commentsLimited)
		{
			var commentsFullInner = BX.findChild(commentsFull, {'tag': 'div', 'className': 'feed-comments-full-inner'}, false);
			var commentsLimitedInner = BX.findChild(commentsLimited, {'tag': 'div', 'className': 'feed-comments-limited-inner'}, false);

			var commentsAll = BX.findChild(container, {'tag': 'div', 'className': 'feed-com-all'}, true);
			if (BX(commentsAll))
			{
				var commentsAllText = BX.findChild(commentsAll, {'tag': 'span', 'className': 'feed-com-all-text'}, true);
				var commentsAllCount = BX.findChild(commentsAll, {'tag': 'span', 'className': 'feed-comments-all-count'}, true);
				var commentsAllHide = BX.findChild(commentsAll, {'tag': 'span', 'className': 'feed-comments-all-hide'}, true);
			}

			//no comments in full mode - make AJAX request
			if (commentsFullInner.innerHTML.length <= 0 && commentsFull.style.display == 'none')
			{
				if (!BX.util.in_array(logID, arrGetComments))
					arrGetComments[arrGetComments.length] = logID;
				else
					return;

				var sonetLXmlHttpGet4 = new XMLHttpRequest();
				var params = "action=get_comments"
					+ "&lang=" + BX.util.urlencode(BX.message('sonetLLangId'))
					+ "&site=" + BX.util.urlencode(BX.message('sonetLSiteId'))
					+ "&stid=" + BX.util.urlencode(BX.message('sonetLSiteTemplateId'))
					+ "&nt=" + BX.util.urlencode(BX.message('sonetLNameTemplate'))
					+ "&sl=" + BX.util.urlencode(BX.message('sonetLShowLogin'))
					+ "&dtf=" + BX.util.urlencode(BX.message('sonetLDateTimeFormat'))
					+ "&as=" + BX.util.urlencode(BX.message('sonetLAvatarSizeComment'))
					+ "&p_user=" + BX.util.urlencode(BX.message('sonetLPathToUser'))
					+ "&p_group=" + BX.util.urlencode(BX.message('sonetLPathToGroup'))
					+ "&p_dep=" + BX.util.urlencode(BX.message('sonetLPathToDepartment'))
					+ "&p_smile=" + BX.util.urlencode(BX.message('sonetLPathToSmile'))
					+ "&logid=" + BX.util.urlencode(logID);

				sonetLXmlHttpGet4.open(
					"get",
					BX.message('sonetLEGetPath') + "?" + BX.message('sonetLSessid')
						+ "&" + params
						+ "&r=" + Math.floor(Math.random() * 1000)
				);
				sonetLXmlHttpGet4.send(null);

				sonetLXmlHttpGet4.onreadystatechange = function()
				{
					if(sonetLXmlHttpGet4.readyState == 4)
					{
						if (BX.util.in_array(logID, arrGetComments))
							for (key in arrGetComments)
								if (arrGetComments[key] == logID)
									arrGetComments.splice(key, 1);

						if(sonetLXmlHttpGet4.status == 200)
						{
							var data = LBlock.DataParser(sonetLXmlHttpGet4.responseText);
							if (typeof(data) == "object")
							{
								if (data[0] == '*')
								{
									if (sonetLErrorDiv != null)
									{
										sonetLErrorDiv.style.display = "block";
										sonetLErrorDiv.innerHTML = sonetLXmlHttpGet4.responseText;
									}
									return;
								}
								sonetLXmlHttpGet4.abort();
								var arComments = data["arComments"];

								for (var i = 0; i < arComments.length; i++)
								{
									anchor_id = Math.floor(Math.random()*100000) + 1;

									contentBlock = false;

									if (
										arComments[i]["EVENT_FORMATTED"]
										&& arComments[i]["EVENT_FORMATTED"]['MESSAGE']
										&& arComments[i]["EVENT_FORMATTED"]['MESSAGE'].length > 0
									)
										comment_message = arComments[i]['EVENT_FORMATTED']['MESSAGE'];
									else
										comment_message = arComments[i]['EVENT']['MESSAGE'];

									if (arComments[i]["AVATAR_SRC"] && arComments[i]["AVATAR_SRC"] != 'undefined')
										avatar = BX.create('div', {
											props: {
												'className': 'feed-com-avatar'
											},
											style: { background: "url('" + arComments[i]["AVATAR_SRC"] + "') no-repeat center #FFFFFF" }
										});
									else
										avatar = BX.create('div', {
											props: {
												'className': 'feed-com-avatar'
											}
										});

									if (
										arComments[i]["EVENT_FORMATTED"] != 'undefined'
										&& arComments[i]["EVENT_FORMATTED"]['DATETIME'] != 'undefined'
									)
										comment_datetime = arComments[i]["EVENT_FORMATTED"]['DATETIME'];
									else
										comment_datetime = arComments[i]["LOG_TIME_FORMAT"];

									if (
										bFollow
										&& parseInt(arComments[i]["LOG_DATE_TS"]) > ts
										&& arComments[i]["EVENT"]["USER_ID"] != BX.message('sonetLCurrentUserID')
									)
										class_name_unread = ' feed-com-block-new';
									else
										class_name_unread = '';

									ratingNode = null;

									if (
										arComments[i]["EVENT"]["RATING_TYPE_ID"].length > 0
										&& arComments[i]["EVENT"]["RATING_ENTITY_ID"] > 0
										&& BX.message("sonetLShowRating") == 'Y'
									)
									{
										if (BX.message("sonetLRatingType") == "like")
										{
											you_like_class = (arComments[i]["EVENT"]["RATING_USER_VOTE_VALUE"] > 0) ? " bx-you-like" : "";
											you_like_text = (arComments[i]["EVENT"]["RATING_USER_VOTE_VALUE"] > 0) ? BX.message("sonetLTextLikeN") : BX.message("sonetLTextLikeY");

											if (arComments[i]["EVENT_FORMATTED"]["ALLOW_VOTE"]['RESULT'])
												vote_text = BX.create('span', {
													props: {
														'className': 'bx-ilike-text'
													},
													html: you_like_text
												});
											else
												vote_text = null;

											ratingNode = BX.create('span', {
												props: {
													'className': 'sonet-log-comment-like rating_vote_text'
												},
												children: [
													BX.create('span', {
														props: {
															'className': 'ilike-light'
														},
														children: [
															BX.create('span', {
																props: {
																	'id': 'bx-ilike-button-' + arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
																	'className': 'bx-ilike-button'
																},
																children: [
																	BX.create('span', {
																		props: {
																			'className': 'bx-ilike-right-wrap' + you_like_class
																		},
																		children: [
																			BX.create('span', {
																				props: {
																					'className': 'bx-ilike-right'
																				},
																				html: arComments[i]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"]
																			})
																		]
																	}),
																	BX.create('span', {
																		props: {
																			'className': 'bx-ilike-left-wrap'
																		},
																		children: [
																			vote_text
																		]
																	})
																]
															}),
															BX.create('span', {
																props: {
																	'id': 'bx-ilike-popup-cont-' + arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
																	'className': 'bx-ilike-wrap-block'
																},
																style: {
																	'display': 'none'
																},
																children: [
																	BX.create('span', {
																		props: {
																			'className': 'bx-ilike-popup'
																		},
																		children: [
																			BX.create('span', {
																				props: {
																					'className': 'bx-ilike-wait'
																				}
																			})
																		]
																	})
																]
															})
														]
													})
												]
											});
										}
										else if (BX.message("sonetLRatingType") == "standart_text")
										{
											ratingNode = BX.create('span', {
												props: {
													'className': 'sonet-log-comment-like rating_vote_text'
												},
												children: [
													BX.create('span', {
														props: {
															'className': 'bx-rating' + (!arComments[i]["EVENT_FORMATTED"]["ALLOW_VOTE"]['RESULT'] ? ' bx-rating-disabled' : '') + (arComments[i]["EVENT"]["RATING_USER_VOTE_VALUE"] != 0 ? ' bx-rating-active' : ''),
															'id': 'bx-rating-' + arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
															'title': (!arComments[i]["EVENT_FORMATTED"]["ALLOW_VOTE"]['RESULT'] ? arComments[i]["EVENT_FORMATTED"]["ERROR_MSG"] : '')
														},
														children: [
															BX.create('span', {
																props: {
																	'className': 'bx-rating-absolute'
																},
																children: [
																	BX.create('span', {
																		props: {
																			'className': 'bx-rating-question'
																		},
																		html: (!arComments[i]["EVENT_FORMATTED"]["ALLOW_VOTE"]['RESULT'] ? BX.message("sonetLTextDenied") : BX.message("sonetLTextAvailable"))
																	}),
																	BX.create('span', {
																		props: {
																			'className': 'bx-rating-yes ' +  (arComments[i]["EVENT"]["RATING_USER_VOTE_VALUE"] > 0 ? '  bx-rating-yes-active' : ''),
																			'title': (arComments[i]["EVENT"]["RATING_USER_VOTE_VALUE"] > 0 ? BX.message("sonetLTextCancel") : BX.message("sonetLTextPlus"))
																		},
																		children: [
																			BX.create('a', {
																				props: {
																					'className': 'bx-rating-yes-count',
																					'href': '#like'
																				},
																				html: ""+parseInt(arComments[i]["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"])
																			}),
																			BX.create('a', {
																				props: {
																					'className': 'bx-rating-yes-text',
																					'href': '#like'
																				},
																				html: BX.message("sonetLTextRatingY")
																			})
																		]
																	}),
																	BX.create('span', {
																		props: {
																			'className': 'bx-rating-separator'
																		},
																		html: '/'
																	}),
																	BX.create('span', {
																		props: {
																			'className': 'bx-rating-no ' +  (arComments[i]["EVENT"]["RATING_USER_VOTE_VALUE"] < 0 ? '  bx-rating-no-active' : ''),
																			'title': (arComments[i]["EVENT"]["RATING_USER_VOTE_VALUE"] < 0 ? BX.message("sonetLTextCancel") : BX.message("sonetLTextMinus"))
																		},
																		children: [
																			BX.create('a', {
																				props: {
																					'className': 'bx-rating-no-count',
																					'href': '#dislike'
																				},
																				html: ""+parseInt(arComments[i]["EVENT"]["RATING_TOTAL_NEGATIVE_VOTES"])
																			}),
																			BX.create('a', {
																				props: {
																					'className': 'bx-rating-no-text',
																					'href': '#dislike'
																				},
																				html: BX.message("sonetLTextRatingN")
																			})
																		]
																	})
																]
															})
														]
													}),
													BX.create('span', {
														props: {
															'id': 'bx-rating-popup-cont-' + arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id + '-plus'
														},
														style: {
															'display': 'none'
														},
														children: [
															BX.create('span', {
																props: {
																	'className': 'bx-ilike-popup  bx-rating-popup'
																},
																children: [
																	BX.create('span', {
																		props: {
																			'className': 'bx-ilike-wait'
																		}
																	})
																]
															})
														]
													}),
													BX.create('span', {
														props: {
															'id': 'bx-rating-popup-cont-' + arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id + '-minus'
														},
														style: {
															'display': 'none'
														},
														children: [
															BX.create('span', {
																props: {
																	'className': 'bx-ilike-popup  bx-rating-popup'
																},
																children: [
																	BX.create('span', {
																		props: {
																			'className': 'bx-ilike-wait'
																		}
																	})
																]
															})
														]
													})
												]
											});
										}
									}

									if (comment_message.length > 0)
										contentBlock = BX.create('div', {
											props: {
												'className': 'feed-com-text'
											},
											children: [
												BX.create('div', {
													props: {
														'className': 'feed-com-text-inner'
													},
													children: [
														BX.create('div', {
															props: {
																'className': 'feed-com-text-inner-inner'
															},
															children: [
																BX.create('span', {
																	html: arComments[i]["EVENT_FORMATTED"]['FULL_MESSAGE_CUT']
																})
															]
														})
													]
												}),
												BX.create('div', {
													props: {
														'className': 'feed-post-text-more'
													},
													'events': {
														'click': BX.delegate(__logCommentExpand, this)
													},
													children: [
														BX.create('div', {
															props: {
																'className': 'feed-post-text-more-but'
															},
															children: [
																BX.create('div', {
																	props: {
																		'className': 'feed-post-text-more-left'
																	}
																}),
																BX.create('div', {
																	props: {
																		'className': 'feed-post-text-more-right'
																	}
																})
															]
														})
													]
												})
											]
										});

									commentsFullInner.appendChild(BX.create('div', {
										props: {
											'className': 'feed-com-block sonet-log-createdby-' + arComments[i]["EVENT"]["USER_ID"] + class_name_unread
										},
										children: [
											avatar,
											BX.create('span', {
												props: {
													'className': 'feed-com-name'
												},
												children: [
													BX.create('a', {
														props: {
															'id': 'anchor_' + anchor_id
														},
														attrs: {
															'href': arComments[i]["CREATED_BY"]["URL"]
														},
														html: arComments[i]["CREATED_BY"]["FORMATTED"]
													})
												]
											}),
											BX.create('div', {
												props: {
													'className': 'feed-com-informers'
												},
												children: [
													BX.create('span', {
														props: {
															'className': 'feed-time'
														},
														html: comment_datetime
													}),
													ratingNode
												]
											}),
											BX.create('div', {
												props: {
													'className': 'feed-com-text-wrap'
												},
												children: [ contentBlock ]
											})
										]
									})
									);

										if (ratingNode)
										{
											if (BX.message("sonetLRatingType") == "like")
											{
												RatingLike.Set(
													arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
													arComments[i]["EVENT"]["RATING_TYPE_ID"],
													arComments[i]["EVENT"]["RATING_ENTITY_ID"],
													(!arComments[i]["EVENT_FORMATTED"]["ALLOW_VOTE"]['RESULT']) ? 'N' : 'Y',
													BX.message('sonetLCurrentUserID'),
													{
														'LIKE_Y' : BX.message('sonetLTextLikeN'),
														'LIKE_N' : BX.message('sonetLTextLikeY'),
														'LIKE_D' : BX.message('sonetLTextLikeD')
													},
													'light',
													BX.message('sonetLPathToUser')
												);
											}
											else if (BX.message("sonetLRatingType") == "standart_text")
											{
												Rating.Set(
													arComments[i]["EVENT"]["RATING_TYPE_ID"] + '-' + arComments[i]["EVENT"]["RATING_ENTITY_ID"] + '-' + anchor_id,
													arComments[i]["EVENT"]["RATING_TYPE_ID"],
													arComments[i]["EVENT"]["RATING_ENTITY_ID"],
													(!arComments[i]["EVENT_FORMATTED"]["ALLOW_VOTE"]['RESULT']) ? 'N' : 'Y',
													BX.message('sonetLCurrentUserID'),
													{
														'PLUS' : BX.message('sonetLTextPlus'),
														'MINUS' : BX.message('sonetLTextMinus'),
														'CANCEL' : BX.message('sonetLTextCancel')
													},
													'light',
													BX.message('sonetLPathToUser')
												);
											}
										}

										BX.tooltip(arComments[i]["EVENT"]["USER_ID"], "anchor_" + anchor_id, "");
								}

								if (BX(commentsAll))
								{
									BX.addClass(commentsAll, 'feed-com-all-expanded');
									if (BX(commentsAllText))
										BX(commentsAllText).style.display = 'none';
									if (BX(commentsAllCount))
										BX(commentsAllCount).style.display = 'none';
									if (BX(commentsAllHide))
										BX(commentsAllHide).style.display = 'inline-block';
								}

								commentsLimited.style.display = 'none';

								var fxStart = 0;
								commentsFull.style.maxHeight = fxStart + 'px';
								commentsFull.style.display = 'block';

								var fxFinish = commentsFullInner.offsetHeight;

								var time = 1.0 * fxFinish / 1200;
								if(time < 0.3)
									time = 0.3;
								if(time > 0.8)
									time = 0.8;

								(new BX.fx({
									time: time,
									step: 0.05,
									type: 'linear',
									start: fxStart,
									finish: fxFinish,
									callback: BX.delegate(__logEventExpandSetHeight, commentsFull),
									callback_complete: BX.delegate(function()
									{
										commentsFull.style.maxHeight = 'none';
									})
								})).start();
							}
						}
						else
						{
							// error!

						}
					}
				}
			}
			//comments in full mode are hidden - show full list
			else if (commentsFull.style.display == 'none')
			{
				commentsFullInner.style.display = 'block';

				if (BX(commentsAll))
				{
					BX.addClass(commentsAll, 'feed-com-all-expanded');
					if (BX(commentsAllText))
						BX(commentsAllText).style.display = 'none';
					if (BX(commentsAllCount))
						BX(commentsAllCount).style.display = 'none';
					if (BX(commentsAllHide))
						BX(commentsAllHide).style.display = 'inline-block';
				}

				commentsLimited.style.display = 'none';
				commentsFull.style.display = 'block';

				var fxStart = 0;
				var fxFinish = commentsFullInner.offsetHeight;

				var time = 1.0 * fxFinish / 1200;
				if(time < 0.3)
					time = 0.3;
				if(time > 0.8)
					time = 0.8;

				(new BX.fx({
					time: time,
					step: 0.05,
					type: 'linear',
					start: fxStart,
					finish: fxFinish,
					callback: BX.delegate(__logEventExpandSetHeight, commentsFull),
					callback_complete: BX.delegate(function()
					{
						commentsFull.style.maxHeight = 'none';
					})
				})).start();
			}
			//show limited list
			else
			{
				commentsLimitedInner.style.display = 'block';

				if (BX(commentsAll))
				{
					BX.removeClass(commentsAll, 'feed-com-all-expanded');
					if (BX(commentsAllText))
						BX(commentsAllText).style.display = 'inline-block';
					if (BX(commentsAllCount))
						BX(commentsAllCount).style.display = 'inline-block';
					if (BX(commentsAllHide))
						BX(commentsAllHide).style.display = 'none';
				}

				var fxStart = commentsFullInner.offsetHeight;
				var fxFinish = 0;

				var time = 1.0 * fxStart / 1200;
				if(time < 0.3)
					time = 0.3;
				if(time > 0.8)
					time = 0.8;

				(new BX.fx({
					time: time,
					step: 0.05,
					type: 'linear',
					start: fxStart,
					finish: fxFinish,
					callback: BX.delegate(__logEventExpandSetHeight, commentsFull),
					callback_complete: BX.delegate(function()
					{
						commentsFull.style.display = 'none';
						commentsLimited.style.display = 'block';
//						commentsLimited.style.maxHeight = commentsLimitedInner.offsetHeight + 'px';
						commentsLimited.style.maxHeight = 'none';
					})
				})).start();
			}
		}
	}
}

function __logCommentShowWait(timeout, log_id)
{
	var comments_block = BX('feed_comments_block_' + log_id);

	var pForm = BX('sonet_log_comment_form_container');
	pForm.style.display = 'none';

	var place = pForm.parentNode;
	if (BX(place))
	{
		var commentLink = BX.findPreviousSibling(place, {'tag': 'div', 'className': 'feed-com-footer'});
		if (BX(commentLink))
			commentLink.style.display = 'block';
	}

	waitDiv = waitDiv || comments_block;
	comments_block = BX(comments_block || waitDiv);

	if (timeout !== 0)
	{
		return (waitTimeout = setTimeout(function(){
			__logCommentShowWait(0, log_id)
		}, timeout || waitTime));
	}

	if (!waitPopup)
	{
		waitPopup = new BX.PopupWindow('log_comment_wait', comments_block, {
			autoHide: true,
			lightShadow: true,
			zIndex: 2,
			content: BX.create('DIV', {props: {className: 'log-comment-wait'}})
		});
	}
	else
		waitPopup.setBindElement(comments_block);

	var height = comments_block.offsetHeight, width = comments_block.offsetWidth;
	if (height > 0 && width > 0)
	{
		waitPopup.setOffset({
			offsetTop: -parseInt(height/2+15),
			offsetLeft: parseInt(width/2-15)
		});

		waitPopup.show();
	}

	return waitPopup;
}

function __logCommentCloseWait()
{
	if (waitTimeout)
	{
		clearTimeout(waitTimeout);
		waitTimeout = null;
	}

	if (waitPopup)
		waitPopup.close();
}

function __logChangeCounter(count)
{
	if (parseInt(count) > 0)
	{
		if (BX("sonet_log_counter_2"))
			BX("sonet_log_counter_2").innerHTML = count;
		if (BX("sonet_log_counter_2_container"))
			BX("sonet_log_counter_2_container").style.display = "block";
	}
	else
	{
		if (BX("sonet_log_counter_2_container"))
			BX("sonet_log_counter_2_container").style.display = "none";
		if (BX("sonet_log_counter_2"))
			BX("sonet_log_counter_2").innerHTML = "0";
	}
}

function __logEventExpand(node)
{
	if (BX(node))
	{
		BX(node).style.display = "none";

		var tmpNode = BX.findParent(BX(node), {'tag': 'div', 'className': 'feed-post-text-block'});
		if (tmpNode)
		{
			var contentContrainer = BX.findChild(tmpNode, {'tag': 'div', 'className': 'feed-post-text-block-inner'}, true);
			var contentNode = BX.findChild(tmpNode, {'tag': 'div', 'className': 'feed-post-text-block-inner-inner'}, true);

			if (contentContrainer && contentNode)
			{
				fxStart = 300;
				fxFinish = contentNode.offsetHeight;

				(new BX.fx({
					time: 1.0 * (contentNode.offsetHeight - fxStart) / (1200 - fxStart),
					step: 0.05,
					type: 'linear',
					start: fxStart,
					finish: fxFinish,
					callback: BX.delegate(__logEventExpandSetHeight, contentContrainer),
					callback_complete: BX.delegate(function()
					{
						contentContrainer.style.maxHeight = 'none';
					})
				})).start();
			}
		}
	}
}

function __logCommentExpand(node)
{
	if (!BX.type.isDomNode(node))
		node = BX.proxy_context;

	if (BX(node))
	{
		var topContrainer = BX.findParent(BX(node), {'tag': 'div', 'className': 'feed-com-text'});
		if (topContrainer)
		{
			BX.remove(node);
			var contentContrainer = BX.findChild(topContrainer, {'tag': 'div', 'className': 'feed-com-text-inner'}, true);
			var contentNode = BX.findChild(topContrainer, {'tag': 'div', 'className': 'feed-com-text-inner-inner'}, true);

			if (contentNode && contentContrainer)
			{
				fxStart = 200;
				fxFinish = contentNode.offsetHeight;

				var time = 1.0 * (fxFinish - fxStart) / (2000 - fxStart);
				if(time < 0.3)
					time = 0.3;
				if(time > 0.8)
					time = 0.8;

				(new BX.fx({
					time: time,
					step: 0.05,
					type: 'linear',
					start: fxStart,
					finish: fxFinish,
					callback: BX.delegate(__logEventExpandSetHeight, contentContrainer),
					callback_complete: BX.delegate(function()
					{
						contentContrainer.style.maxHeight = 'none';
					})
				})).start();
			}
		}
	}
}

function __logEventExpandSetHeight(height)
{
	this.style.maxHeight = height + 'px';
}

function __logShowHiddenDestination(log_id, created_by_id, bindElement)
{

	var sonetLXmlHttpSet6 = new XMLHttpRequest();

	sonetLXmlHttpSet6.open("POST", BX.message('sonetLESetPath'), true);
	sonetLXmlHttpSet6.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

	sonetLXmlHttpSet6.onreadystatechange = function()
	{
		if(sonetLXmlHttpSet6.readyState == 4)
		{
			if(sonetLXmlHttpSet6.status == 200)
			{
				var data = LBlock.DataParser(sonetLXmlHttpSet6.responseText);
				if (typeof(data) == "object")
				{
					if (data[0] == '*')
					{
						if (sonetLErrorDiv != null)
						{
							sonetLErrorDiv.style.display = "block";
							sonetLErrorDiv.innerHTML = sonetLXmlHttpSet6.responseText;
						}
						return;
					}
					sonetLXmlHttpSet6.abort();
					var arDestinations = data["arDestinations"];
					
					if (typeof (arDestinations) == "object")
					{
						if (BX(bindElement))
						{
							var cont = bindElement.parentNode;
							cont.removeChild(bindElement);
							var url = '';

							for (var i = 0; i < arDestinations.length; i++)
							{
								if (typeof (arDestinations[i]['TITLE']) != 'undefined' && arDestinations[i]['TITLE'].length > 0)
								{
									cont.appendChild(BX.create('SPAN', {
										html: ',&nbsp;'
									}));

									if (typeof (arDestinations[i]['URL']) != 'undefined' && arDestinations[i]['URL'].length > 0)
										cont.appendChild(BX.create('A', {
											props: {
												className: 'feed-add-post-destination-new',
												'href': arDestinations[i]['URL']
											},
											html: arDestinations[i]['TITLE']
										}));
									else
										cont.appendChild(BX.create('SPAN', {
											props: {
												className: 'feed-add-post-destination-new'
											},
											html: arDestinations[i]['TITLE']
										}));
								}
							}

							if (
								data["iDestinationsHidden"] != 'undefined'
								&& parseInt(data["iDestinationsHidden"]) > 0
							)
							{
								data["iDestinationsHidden"] = parseInt(data["iDestinationsHidden"]);
								if (
									(data["iDestinationsHidden"] % 100) > 10
									&& (data["iDestinationsHidden"] % 100) < 20
								)
									var suffix = 5;
								else
									var suffix = data["iDestinationsHidden"] % 10;

								cont.appendChild(BX.create('SPAN', {
									html: '&nbsp;' + BX.message('sonetLDestinationHidden' + suffix).replace("#COUNT#", data["iDestinationsHidden"])
								}));
							}
						}
					}
				}
			}
			else
			{
				// error!
			}
		}
	}

	sonetLXmlHttpSet6.send("r=" + Math.floor(Math.random() * 1000)
		+ "&" + BX.message('sonetLSessid')
		+ "&site=" + BX.util.urlencode(BX.message('SITE_ID'))
		+ "&nt=" + BX.util.urlencode(BX.message('sonetLNameTemplate'))
		+ "&log_id=" + encodeURIComponent(log_id)
		+ (created_by_id ? "&created_by_id=" + encodeURIComponent(created_by_id) : "")
		+ "&p_user=" + BX.util.urlencode(BX.message('sonetLPathToUser'))
		+ "&p_group=" + BX.util.urlencode(BX.message('sonetLPathToGroup'))
		+ "&p_dep=" + BX.util.urlencode(BX.message('sonetLPathToDepartment'))
		+ "&dlim=" + BX.util.urlencode(BX.message('sonetLDestinationLimit'))
		+ "&action=get_more_destination"
	);

}

function __logSetFollow(log_id)
{
	var strFollowOld = (BX("log_entry_follow_" + log_id, true).getAttribute("data-follow") == "Y" ? "Y" : "N");
	var strFollowNew = (strFollowOld == "Y" ? "N" : "Y");	

	if (BX("log_entry_follow_" + log_id, true))
	{
		BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetLFollow' + strFollowNew);
		BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", strFollowNew);
	}
				
	BX.ajax({
		url: BX.message('sonetLSetPath'),
		method: 'POST',
		dataType: 'json',
		data: {
			"log_id": log_id,
			"action": "change_follow",
			"follow": strFollowNew,
			"sessid": BX.bitrix_sessid(),
			"site": BX.message('sonetLSiteId')
		},
		onsuccess: function(data) {
			if (
				data["SUCCESS"] != "Y"
				&& BX("log_entry_follow_" + log_id, true)
			)
			{
				BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetLFollow' + strFollowOld);
				BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", strFollowOld);
			}
		},
		onfailure: function(data) {
			if (BX("log_entry_follow_" +log_id, true))
			{
				BX.findChild(BX("log_entry_follow_" + log_id, true), { tagName: 'a' }).innerHTML = BX.message('sonetLFollow' + strFollowOld);
				BX("log_entry_follow_" + log_id, true).setAttribute("data-follow", strFollowOld);
			}		
		}
	});
	return false;
}