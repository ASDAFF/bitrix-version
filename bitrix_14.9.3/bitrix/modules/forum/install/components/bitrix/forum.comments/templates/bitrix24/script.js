;(function(window){
	window.__fcOnUCFormClear = function(obj) {
		var form = obj.form,
			files = form["UF_FORUM_MESSAGE_DOC[]"];
		var end = false, file = false;
		if(files !== null && typeof files != 'undefined')
		{
			end = false; file = false;
			do
			{
				if (!!form["UF_FORUM_MESSAGE_DOC[]"])
				{
					if (!!form["UF_FORUM_MESSAGE_DOC[]"][0]) {
						file = form["UF_FORUM_MESSAGE_DOC[]"][0];
					} else {
						file = form["UF_FORUM_MESSAGE_DOC[]"];
						end = true;
					}
					if (!!window.wduf_places && !!window.wduf_places[file.value])
						window.wduf_places[file.value] = null;
					while(BX('wd-doc' + file.value))
						BX.remove(BX('wd-doc' + file.value));
					BX.remove(file);
				}
				else {
					end = true;
				}
			} while (!end);
		}
		files = form["FILE_NEW[]"];
		if(files !== null && typeof files != 'undefined')
		{
			end = false; file = false;
			do
			{
				if(!!form["FILE_NEW[]"])
				{
					if (!!form["FILE_NEW[]"][0]) {
						file = form["FILE_NEW[]"][0];
					} else {
						file = form["FILE_NEW[]"];
						end = true;
					}
					while(BX('wd-doc' + file.value))
						BX.remove(BX('wd-doc' + file.value));
					BX.remove(file);
				}
				else
				{
					end = true;
				}
			} while (!end);
		}
	};
	window.__fcOnUCFormAfterShow = function(obj, text, data) {
		var post_data = {MID : obj.id[1], ENTITY_XML_ID : obj.id[0], ENTITY_TYPE : obj.entitiesId[obj.id[0]][0], ENTITY_ID : obj.entitiesId[obj.id[0]][1]}, ii;
		for (ii in post_data)
		{
			if (typeof ii == "string" && (ii.indexOf("MID") === 0 || ii.indexOf("ENTITY") === 0))
			{
				if (!obj.form[ii])
					obj.form.appendChild(BX.create('INPUT', {attrs : {name : ii, type: "hidden"}}));
				obj.form[ii].value = post_data[ii];
			}
		}
		var mpFormObj = window['PlEditor' + obj.form.id], node, res, tmp;
		if (!!mpFormObj && !!data)
		{
			if (!!data["UF"] && !!data["UF"]["UF_FORUM_MESSAGE_DOC"] &&
				!!data["UF"]["UF_FORUM_MESSAGE_DOC"]["VALUE"] &&
				data["UF"]["UF_FORUM_MESSAGE_DOC"]["VALUE"].length > 0)
			{
				var docs = data["UF"]["UF_FORUM_MESSAGE_DOC"]["VALUE"],
					arRes = [];
				tmp = null;
				if (!mpFormObj.WDController && !!mpFormObj.WDControllerInit)
				{
					tmp = [obj.id[0], obj.id[1]];
					BX.addCustomEvent( BX.findParent(BX.findChild(obj.form, {'className': 'wduf-selectdialog'}, true, false)),
						'WDLoadFormControllerInit',
						BX.delegate(function(obj1) { obj.show(tmp, text, data, false); }, this)
					);
					obj.id = null;
					return mpFormObj.WDControllerInit();
				}
				if (mpFormObj.WDController && !mpFormObj.WDController.onLightEditorShowObj)
				{
					mpFormObj.WDController.onLightEditorShowObj = [];
					BX.addCustomEvent(
						BX.findParent(BX.findChild(obj.form, {'className': 'wduf-selectdialog'}, true, false)),
						'OnFileUploadSuccess',
						function(result, obj) {
							if (obj.dialogName == 'AttachFileDialog' && BX.util.in_array(result['element_id'], obj['onLightEditorShowObj'])) {
								mpFormObj.oEditor.SaveContent();
								var content = mpFormObj.oEditor.GetContent();
								content = content.replace(new RegExp('\\&\\#91\\;DOCUMENT ID=(' + result['element_id'] + ')([WIDTHHEIGHT=0-9 ]*)\\&\\#93\\;','gim'), '[DOCUMENT ID=$1$2]');
								mpFormObj.oEditor.SetContent(content);
								mpFormObj.oEditor.SetEditorContent(mpFormObj.oEditor.content);
								mpFormObj.oEditor.SetFocus();
								mpFormObj.oEditor.AutoResize();
							}
						}
					);
				}
				if (mpFormObj.WDController)
					mpFormObj.WDController.onLightEditorShowObj = [];
				while ((res = docs.pop()) && !!res)
				{
					var node1 = BX('wdif-doc-' + res);
					node = (!!node1 ? (node1.tagName == "A" ? node1 : BX.findChild(node1, {'tagName' : "IMG"}, true)) : null);
					tmp = {
						'element_id' : res,
						'element_url' : '',
						'element_name' : '',
						'element_content_type' : (!!node && node.tagName == "IMG" ? 'image/xyz' : 'notimage/xyz'),
						'storage' : 'webdav'
					};
					if (!!node)
					{
						tmp['element_url'] = (node.tagName == "A" ? node.href : node.src);
						tmp['element_name'] = node.getAttribute("alt");
						tmp['width'] = node.getAttribute("data-bx-width");
						tmp['height'] = node.getAttribute("data-bx-height");
						mpFormObj.checkFile(res, tmp);
					}

					if (mpFormObj.WDController)
					{
						if (!!node)
							tmp['element_url'] = node.getAttribute("data-bx-document");
						arRes.push(tmp);
						mpFormObj.WDController.onLightEditorShowObj.push(res);
					}
				}
				if (mpFormObj.WDController && arRes.length > 0)
				{
					mpFormObj.WDControllerInit('show');
					mpFormObj.WDController.agent.values = arRes;
					BX.onCustomEvent(mpFormObj.WDController.controller.parentNode, 'OnFileFromDialogSelected', [mpFormObj.WDController.agent.values, mpFormObj.WDController]);
					mpFormObj.WDController.agent.ShowAttachedFiles();
				}
			}

			mpFormObj.arFiles = {};

			if (!!data["FILES"])
			{
				if (!mpFormObj.FController && !!mpFormObj.FControllerInit)
				{
					tmp = [obj.id[0], obj.id[1]];
					BX.addCustomEvent(mpFormObj.FControllerNode, 'BFileDLoadFormControllerInit', BX.delegate(function(obj1) { obj.show(tmp, text, data, false); }, obj));
					obj.id = null;
					return mpFormObj.FControllerInit();
				}
				var file = null, arRes1 = [];
				mpFormObj.FControllerInit('show');
				for (ii in data["FILES"])
				{
					if (ii && !!typeof data["FILES"][ii] && typeof data["FILES"][ii] == "object")
					{
						file = {
							id : data["FILES"][ii]["FILE_ID"],
							element_id : data["FILES"][ii]["FILE_ID"],
							element_name : data["FILES"][ii]["FILE_NAME"],
							element_size : data["FILES"][ii]["FILE_SIZE"],
							element_content_type: data["FILES"][ii]["CONTENT_TYPE"],
							element_url: data["FILES"][ii]["SRC"],
							element_thumbnail: data["FILES"][ii]["SRC"],
							element_image: data["FILES"][ii]["THUMBNAIL"],
							storage : 'bfile' };

						if (mpFormObj.checkFile(file['id'], file))
						{
							arRes1.push(file);
						}
					}
				}
				if (!!arRes1 && !!mpFormObj.FController)
				{
					if (!!mpFormObj.FController._CID)
						mpFormObj.FController._CID = mpFormObj.FController.CID;
					mpFormObj.FController.CID = BX.message('FCCID');

					while ((file = arRes1.pop()) && !!file)
					{
						mpFormObj.FController.agent.values = [file];
						mpFormObj.FController.agent.ShowAttachedFiles();
					}
				}
			}
		}
	}
})(window);