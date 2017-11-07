//$(document).bind('mobileinit', onMobileInit);

function GenNewSecret()
{
	var instr = "0123456789ABCDEF";
	var ret= '';
	for (var c=0; c<48; c++) 
	{
		ret += instr[Math.floor(Math.random() * instr.length)];
	}
	return ret;
}
function getLang()
{
	var l = navigator.language.toUpperCase() + ';' + navigator.appVersion.toUpperCase();
	if(l.indexOf("RU-")>-1)
		return 'ru';
	if(l.indexOf("DE-")>-1)
		return 'de';
	return 'en';
}

var db;
function startApp()
{
	var shortName = 'OTP';
	var version = '1.0';
	var displayName = 'OTP';
	var maxSize = 65536;
	db = openDatabase(shortName, version, displayName, maxSize);
	db.transaction(
		function(transaction) {
			transaction.executeSql(
				'CREATE TABLE IF NOT EXISTS sites ' +
				' (id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, ' +
				' url TEXT NOT NULL, ' +
				' name TEXT NOT NULL, ' +
				' secret TEXT NOT NULL, ' +
				' position INTEGER NOT NULL );'
			);
		}
	);
	
}

var curMESS;

function GetMessage(id)
{
	return curMESS[id];
}

function OnMobileInit()
{
	curMESS = MESS[getLang()];
	$("[data-lang]").each(function ()
			{
				if(curMESS[this.attributes['data-lang'].value])
				{
					if(this.attributes['data-lang-attr'])
						this.attributes[this.attributes['data-lang-attr'].value].value = curMESS[this.attributes['data-lang'].value];
					else
						this.innerHTML = curMESS[this.attributes['data-lang'].value];
				}
			}
		);
}

$(document).ready(startApp);
$(document).ready(OnMobileInit);


function rl(n,s) 
{
	return ( n << s ) | (n >>> (32-s));
};

function cvt(val) 
{
	var str="";
	var i;
	var v;
	for( i=7; i>=0; i-- )
	{
		v = (val >>> (i*4))&0x0f;
		str = str+ v.toString(16);
	}
	return str;
};

function SHA1 (str) 
{
	var blk;
	var i, j;
	var W = new Array(80);
	var h0 = 0x67452301;
	var h1 = 0xEFCDAB89;
	var h2 = 0x98BADCFE;
	var h3 = 0x10325476;
	var h4 = 0xC3D2E1F0;
	var a, b, c, d, e;
	var temp;
	var msg_length = str.length;
	var words = new Array();

	for( i=0; i<msg_length-3; i+=4 ) 
		words.push(str.charCodeAt(i) << 24 | str.charCodeAt(i+1) << 16 |	str.charCodeAt(i+2) << 8 | str.charCodeAt(i+3));
 
	switch( msg_length % 4 ) 
	{
		case 0:
			i = 0x080000000;
		break;
		case 1:
			i = str.charCodeAt(msg_length-1) << 24 | 0x0800000;
		break;
		case 2:
			i = str.charCodeAt(msg_length-2) << 24 | str.charCodeAt(msg_length-1) << 16 | 0x08000;
		break;
		case 3:
			i = str.charCodeAt(msg_length-3) << 24 | str.charCodeAt(msg_length-2) << 16 | str.charCodeAt(msg_length-1) << 8	| 0x80;
		break;
	}
 
	words.push( i );
	while((words.length % 16) != 14) 
		words.push( 0 );

	words.push( msg_length >>> 29 );
	words.push( (msg_length << 3) & 0x0ffffffff );
	for ( blk=0; blk<words.length; blk+=16 ) 
	{
 
		for( i=0; i<16; i++ )
			W[i] = words[blk+i];
		for( i=16; i<=79; i++ )
			W[i] = rl(W[i-3] ^ W[i-8] ^ W[i-14] ^ W[i-16], 1);
 
		a = h0;
		b = h1;
		c = h2;
		d = h3;
		e = h4;
 
		for( i= 0; i<=19; i++ ) 
		{
			temp = (rl(a,5) + ((b&c) | (~b&d)) + e + W[i] + 0x5A827999) & 0x0ffffffff;
			e = d;
			d = c;
			c = rl(b,30);
			b = a;
			a = temp;
		}
 
		for( i=20; i<=39; i++ ) 
		{
			temp = (rl(a,5) + (b ^ c ^ d) + e + W[i] + 0x6ED9EBA1) & 0x0ffffffff;
			e = d;
			d = c;
			c = rl(b,30);
			b = a;
			a = temp;
		}
 
		for( i=40; i<=59; i++ ) 
		{
			temp = (rl(a,5) + ((b&c) | (b&d) | (c&d)) + e + W[i] + 0x8F1BBCDC) & 0x0ffffffff;
			e = d;
			d = c;
			c = rl(b,30);
			b = a;
			a = temp;
		}
 
		for( i=60; i<=79; i++ ) 
		{
			temp = (rl(a,5) + (b ^ c ^ d) + e + W[i] + 0xCA62C1D6) & 0x0ffffffff;
			e = d;
			d = c;
			c = rl(b,30);
			b = a;
			a = temp;
		}
 
		h0 = (h0 + a) & 0x0ffffffff;
		h1 = (h1 + b) & 0x0ffffffff;
		h2 = (h2 + c) & 0x0ffffffff;
		h3 = (h3 + d) & 0x0ffffffff;
		h4 = (h4 + e) & 0x0ffffffff;
	}
	return cvt(h0) + cvt(h1) + cvt(h2) + cvt(h3) + cvt(h4);
}

function hextobin(s) 
{
	var ret = '';
	for (var i = 0; i < s.length; i = i + 2) 
	{
		ret += String.fromCharCode(parseInt('0x' + s[i] + s[i+1]));
	}
	return ret;
}

function strpad(str, len, chr)
{
	var ret='';
	while (str.length < len) 
	{
		str =	str + chr;
	}
	return str;
}

function strrpad(str, len, chr)
{
	var ret='';
	while (str.length < len) 
	{
		str = chr + str;
	}
	return str;
}

function dectohex(x)
{
	return Number(x).toString(16);
}

function xor(a, b)
{
	var i=0;
	var r='';
	while(i<a.length || i<b.length)
	{
		if(i>=a.lenght)
			r+=b[i];
		else if(i>=a.lenght)
			r+=a[i];
		else
			r+=String.fromCharCode(a.charCodeAt(i) ^ b.charCodeAt(i));
		i++;
	}
	return r;
}

function hmacsha1(data, key)
{
	key = strpad(key, 64*2, '0');
	data=dectohex(data);
	data = strrpad(data, 16, '0');
	data=hextobin(data);
	ipad = strpad('', 64, hextobin('36') );
	opad = strpad('', 64, hextobin('5C'));
	key=hextobin(key);
	hmac = hextobin(SHA1(xor(key,	opad) + hextobin(SHA1( xor(key,	ipad) + data))));
	return hmac;
}

function HOTP(cnt, secret)
{
	sha1_hash = hmacsha1(cnt, secret);
	dwOffset = sha1_hash.charCodeAt(sha1_hash.length - 1); //hexdec(substr($sha1_hash, -1, 1));
	dwOffset = dwOffset % 16;
	dbc1 = sha1_hash.charCodeAt(dwOffset)*256*256*256+
	sha1_hash.charCodeAt(dwOffset+1)*256*256+
	sha1_hash.charCodeAt(dwOffset+2)*256+
	sha1_hash.charCodeAt(dwOffset+3);
	dbc2 = dbc1 & 0x7fffffff;
	hotp = dbc2 % 1000000;
	hotp = '' + hotp;
	while(hotp.length<6)
		hotp = '0' + hotp;
	return hotp;
}

function toout(h) 
{
	l = h[19] & 0xf;
	v = (h[l] & 0x7f) << 24 | (h[l + 1] & 0xff) << 16 | (h[l + 2] & 0xff) << 8 | (h[l + 3] & 0xff);
	v = "" + v;
	v = v.substr(v.length - 6, 6);
	return v;
}

