;
(function (window)
{
	if (window.BX.indexedDB) return;

	var BX = window.BX;

	/**
	 * Parameters description:
	 * name - name of the database*
	 * version - version of the database
	 * createCallback - version of the database
	 * @param params
	 */

	BX.indexedDB = function (params)
	{
		var indexedDB = window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB;
		window.IDBTransaction = window.IDBTransaction || window.webkitIDBTransaction || window.msIDBTransaction;
		window.IDBKeyRange = window.IDBKeyRange || window.webkitIDBKeyRange || window.msIDBKeyRange;

		if(
			typeof indexedDB != 'undefined'
			&& typeof window.IDBTransaction != 'undefined'
			&& typeof window.IDBKeyRange != 'undefined'
		)
		{
			var request = indexedDB.open(params.name, parseInt(params.version));

			request.onsuccess = function(event) {
				if (typeof params.callback == 'function')
				{
					params.callback(this.result);
				}
			};

			request.onupgradeneeded = function (event)
			{
				/* syncronize database structure */

				if (typeof params.oScheme != 'undefined')
				{
	                var hDBHandle = event.target.result;
					var ob = null;
					var oStore = null;
					var tx = null;
					var schemeLength = params.oScheme.length;

					for (var i = 0; i < schemeLength; i++)
					{
						ob = params.oScheme[i];

						if (
							typeof ob == 'object'
							&& !hDBHandle.objectStoreNames.contains(ob.name)
						)
						{
							oStore = hDBHandle.createObjectStore(
								ob.name,
								{
									keyPath : (typeof ob.keyPath != 'undefined' && ob.keyPath),
									autoIncrement : (typeof ob.autoIncrement != 'undefined' && !!ob.autoIncrement)
								}
							);

							if (typeof ob.indexes != 'undefined')
							{
								for (var j = 0; j < ob.indexes.length; j++)
								{
									oStore.createIndex(ob.indexes[j].name, ob.indexes[j].keyPath, { unique: !!ob.indexes[j].unique });
								}
							}
						}
					}

					var bFound = null;
					length = hDBHandle.objectStoreNames.length;

					for (var i = 0; i < length; i++)
					{
						bFound = false;

						for (var j = 0; j < schemeLength; j++)
						{
							ob = params.oScheme[j];
							if (ob.name == hDBHandle.objectStoreNames[i])
							{
								bFound = true;
								continue;
							}
						}

						if (!bFound)
						{
							hDBHandle.deleteObjectStore(hDBHandle.objectStoreNames[i]);
						}
					}
				}
			}
		}
	};

	BX.indexedDB.checkDbObject = function (dbObject)
	{
		return (typeof dbObject == 'object');
	}

	BX.indexedDB.getObjectStore = function (dbObject, storeName, mode)
	{
		if (!BX.indexedDB.checkDbObject(dbObject))
		{
			return;
		}

		try
		{
			var tx = dbObject.transaction(storeName, mode);
			tx.onsuccess = function(){};
			tx.onerror = function(){};
			return tx.objectStore(storeName);
		}
		catch(err)
		{
			return false;
		}
	}

	BX.indexedDB.addValue = function (dbObject, storeName, value, key, obCallback)
	{
		var request = null;

		try
		{
			request = BX.indexedDB.getObjectStore(dbObject, storeName, 'readwrite').add(value, key);
		}
		catch(e)
		{
		}

		request.onerror = function(event)
		{
			if (typeof obCallback.error == 'function')
			{
				obCallback.error(event);
			}
		};

		request.onsuccess = function(event)
		{
			if (typeof obCallback.callback == 'function')
			{
				obCallback.callback(event);
			}
		};
	};

	BX.indexedDB.updateValue = function (dbObject, storeName, value, key, obCallback)
	{
		var store = BX.indexedDB.getObjectStore(dbObject, storeName, 'readwrite');
		var request = null;

		try
		{
			request = store.put(value);
		}
		catch (e)
		{
		}

		request.onerror = function(event)
		{
			if (
				typeof obCallback != 'undefined'
				&& typeof obCallback.error == 'function'
			)
			{
				obCallback.error(event, key);
			}
		};

		request.onsuccess = function(event)
		{
			if (
				typeof obCallback != 'undefined'
				&& typeof obCallback.callback == 'function'
			)
			{
				obCallback.callback(event);
			}
		};
	};

	BX.indexedDB.deleteValue = function (dbObject, storeName, key, obCallback)
	{
		var request = BX.indexedDB.getObjectStore(dbObject, storeName, 'readwrite')['delete'](key);

		request.onerror = function(event)
		{
			if (
				typeof obCallback != 'undefined'
				&& typeof obCallback.error == 'function'
			)
			{
				obCallback.error(event);
			}
		};

		request.onsuccess = function(event)
		{
			if (
				typeof obCallback != 'undefined'
				&& typeof obCallback.callback == 'function'
			)
			{
				obCallback.callback(event);
			}
		};
   };

	BX.indexedDB.deleteValueByIndex = function (dbObject, storeName, indexName, key, obCallback)
	{
		var getKeyRequest = null;

		try
		{
			getKeyRequest = BX.indexedDB.getObjectStore(dbObject, storeName, 'readwrite').index(indexName).getKey(key);
		}
		catch(e)
		{
		}

		getKeyRequest.onsuccess = function(event)
		{
			var deleteRequest = null;

			try
			{
				deleteRequest = BX.indexedDB.getObjectStore(dbObject, storeName, 'readwrite')['delete'](event.target.result);

				deleteRequest.onsuccess = function(event)
				{
					if (
						typeof obCallback != 'undefined'
						&& typeof obCallback.callback == 'function'
					)
					{
						obCallback.callback(event);
					}
				};

				deleteRequest.onerror = function(event)
				{
					if (
						typeof obCallback != 'undefined'
						&& typeof obCallback.error == 'function'
					)
					{
						obCallback.error(event);
					}
				};
			}
			catch(e)
			{
			}
		};

		getKeyRequest.onerror = function(event)
		{
			if (typeof obCallback.error == 'function')
			{
				obCallback.error(event);
			}
		};
   };

	BX.indexedDB.getValue = function (dbObject, storeName, key, obCallback)
	{
		var request = BX.indexedDB.getObjectStore(dbObject, storeName, 'readonly').get(key);

		request.onerror = function(event)
		{
			if (typeof obCallback.error == 'function')
			{
				obCallback.error(event);
			}
		};

		request.onsuccess = function(event)
		{
			if (typeof obCallback.callback == 'function')
			{
				obCallback.callback(event.target.result);
			}
		};
	};

	BX.indexedDB.getValueByIndex = function (dbObject, storeName, indexName, key, obCallback)
	{
		var request = BX.indexedDB.getObjectStore(dbObject, storeName, 'readonly').index(indexName).get(key);

		request.onerror = function(event)
		{
			if (typeof obCallback.error == 'function')
			{
				obCallback.error(event);
			}
		};

		request.onsuccess = function(event)
		{
			if (typeof obCallback.callback == 'function')
			{
				obCallback.callback(event.target.result);
			}
		};
	};

	BX.indexedDB.openCursor = function (dbObject, storeName, obKeyRange, obCallback)
	{
		var keyRange = null;

		if (typeof obKeyRange.lower != 'undefined')
		{
			if (typeof obKeyRange.upper != 'undefined')
			{
				keyRange = window.IDBKeyRange.bound(obKeyRange.lower, obKeyRange.upper, !!obKeyRange.lowerOpen, !!obKeyRange.upperOpen);
			}
			else
			{
				keyRange = window.IDBKeyRange.lowerBound(obKeyRange.lower, !!obKeyRange.lowerOpen);
			}
		}
		else if (typeof obKeyRange.upper != 'undefined')
		{
			keyRange = window.IDBKeyRange.upperBound(obKeyRange.upper, !!obKeyRange.upperOpen);
		}

		var request = BX.indexedDB.getObjectStore(dbObject, storeName, 'readonly').openCursor(keyRange);

		request.onerror = function(event)
		{
			if (typeof obCallback.error == 'function')
			{
				obCallback.error(event);
			}
		};

		request.onsuccess = function(event)
		{
			if (typeof obCallback.callback == 'function')
			{
				var cursor = event.target.result;
				if (cursor)
				{
					obCallback.callback(cursor.value);
					cursor['continue']();
                }
			}
		};
	};

	BX.indexedDB.count = function (dbObject, storeName, obCallback)
	{
		var request = BX.indexedDB.getObjectStore(dbObject, storeName, 'readonly').count();

		request.onerror = function(event)
		{
			if (typeof obCallback.error == 'function')
			{
				obCallback.error(event);
			}
		};

		request.onsuccess = function()
		{
			if (typeof obCallback.callback == 'function')
			{
				obCallback.callback(request.result);
			}
		};
	};

	BX.indexedDB.deleteDatabase = function (databaseName, obCallback)
	{
		var indexedDB = window.indexedDB || window.mozIndexedDB || window.webkitIndexedDB || window.msIndexedDB;
		indexedDB.deleteDatabase(databaseName);
	}

})(window);