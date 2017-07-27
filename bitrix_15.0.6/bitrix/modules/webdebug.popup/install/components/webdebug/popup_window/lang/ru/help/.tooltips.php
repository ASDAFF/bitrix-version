<?
$MESS['AREA_FILE_SHOW_TIP'] = "Настройка позволяет выбрать область действия включаемой области:<br /><i>для страницы</i> - включаемая область будет включена только в текущую страницу;<br /><i>для раздела</i> - область будет являться включаемой для всего раздела.";
$MESS['AREA_FILE_SUFFIX_TIP'] = "Указывается суффикс имени файла, который будет добавляться ко всем создаваемым в дальнейшем включаемым областям. Страницы с таким суффиксом будут восприниматься как включаемые области.";
$MESS['EDIT_MODE_TIP'] = "Выбранный режим редактирования будет использоваться при переходе к редактированию включаемой области страницы/раздела из публичной части.";
$MESS['EDIT_TEMPLATE_TIP'] = "Выводятся все шаблоны страниц, созданные в системе. Они располагаются в разделе /bitrix/templates/.default/page_templates/. Можно выбрать пункт <i>другое</i> и указать полный путь к файлу-шаблону.";
$MESS['AREA_FILE_RECURSIVE_TIP'] = "При установленном флаге включаемые области разделов будут подключаться рекурсивно, т.е. если в папке более низкого уровня есть своя включаемая область, то она будет показана. Если текущий раздел не имеет своей включаемой области, то рекурсивно до корня сайта будут проверяться все разделы до самого верхнего и будет выведена первая встретившаяся включаемая область.";

$MESS['POPUP_ID_TIP'] = "Идентификатор (ID) всплывающего окна, только латинские символы, цифры и символ подчеркивания. Например, \"test\".";
$MESS['POPUP_NAME_TIP'] = "Заголовок всплывающего окна, отображаемый сверху всплывающего окна.";
$MESS['POPUP_WIDTH_TIP'] = "Ширина всплывающего окна (в пикселях), минимум - 300 пикселей.";
$MESS['POPUP_CLOSE_TIP'] = "Наличие кнопки закрытия окна, данный параметр также влияет на возможность закрытия всплывающего окна при клике на затемняющий фон.";
$MESS['POPUP_APPEND_TO_BODY_TIP'] = "Если галочка отмечена, всплывающее окно будет перемещено внутрь тела страницы (BODY), если галочка не отмечена - окно останется в текущем контейнере. Данную опцию нужно включить, если окно при открытии смещено от того места, в котором должно появиться.";
$MESS['POPUP_DISPLAY_NONE_TIP'] = "При отмеченной галочке окно будет скрываться использование display:none, это важно для всплывающих окон, содержащи видео, и другие Flash-элементы";
$MESS['POPUP_ANIMATION_TIP'] = "Эффект анимации, используемый при открытии всплывающего окна (возможные значения: fadeAndPop - падение сверху, fade - появление, none - без анимации)";
$MESS['POPUP_CALLBACK_INIT_TIP'] = "JavaScript-функция, вызываемая при инициализации всплывающего окна (после загрузке страницы функция выполнится один раз).";
$MESS['POPUP_CALLBACK_OPEN_TIP'] = "JavaScript-функция, вызываемая при первом окрытии всплывающего окна (после загрузке страницы функция выполнится один раз).";
$MESS['POPUP_CALLBACK_SHOW_TIP'] = "JavaScript-функция, вызываемая при каждом окрытии всплывающего окна.";
$MESS['POPUP_CLASSES_TIP'] = "CSS-классы, добавляемые всплывающим окнам. Класс задает дефолтные стили ысплывающих окон (например, \"wd_popup_style_01\" или \"wd_popup_style_03\")";
$MESS['POPUP_LINK_SHOW_TIP'] = "Если отметить галочку, то данный компонент выводит также ссылку для открытия всплывающего окна, в противном случае возможно лишь назначить открытие всплывающего окна с помощью jQuery-селектора (назначить возможно любому элементу, поддерживающему метод .click())";
$MESS['POPUP_LINK_TO_TIP'] = "jQuery-селектор элемента (или нескольких элементов), по клику на которые должно открываться всплывающее окно.";
$MESS['POPUP_LINK_TEXT_TIP'] = "Текст ссылки, открывающей всплывающее окно (только если выбрана галочка \"Показывать ссылку\").";
$MESS['POPUP_AUTOOPEN_TIP'] = "Отметьте галочку, чтобы окно открывалось автоматически после загрузки страницы.";
$MESS['POPUP_AUTOOPEN_DELAY_TIP'] = "Задержка между открытием страницы (в миллисекундах) и появлением всплывающего окна.";
$MESS['POPUP_LINK_HIDDEN_TIP'] = "Отметьте галочку, чтобы ссылка была невидимой.";
?>