<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
IncludeModuleLangFile(__FILE__);

$arMobileMenuItems = array(
	array(
		"type" => "section",
		"text" =>"������� �������",
		"sort" => "100",
		"items" =>	array(
			array(
				"text" => "������� ��������",
				"data-url" => SITE_DIR."eshop_app/",
				"class" => "menu-item",
				"data-pageid" => "main",
				"id" => "main"
			),
			array(
				"text" => "�������",
				"class" => "menu-item",
				"onclick" => "openSectionList();",
				"data-pageid" => "catalog",
				"id" => "catalog"
			),
			array(
				"text" => "�������",
				"class" => "menu-item",
				"data-url" => SITE_DIR."eshop_app/personal/cart/",
				"id" => "cart",
				"data-pageid" => "cart",
			),
		)
	),
	array(
		"type" => "section",
		"text" =>"����������",
		"sort" => "200",
		"items" =>	array(
			array(
				"text" => "� ��������",
				"data-url" => SITE_DIR."eshop_app/about/",
				"class" => "menu-item",
				"id" => "about",
				"data-pageid" => "about",
			),
		)
	)
);
if (CBXFeatures::IsFeatureEnabled('SaleAccounts'))
	$arMobileMenuItems[] = array(
		"type" => "section",
		"text" =>"��� ������",
		"sort" => "300",
		"items" =>	array(
			array(
				"text" => "������ �������",
				"data-url" => SITE_DIR."eshop_app/personal/order/order_list.php",
				"class" => "menu-item",
				"id" => "orders",
			),
			array(
				"text" => "������� ������",
				"data-url" => SITE_DIR."eshop_app/personal/order/order_list.php?action=get_filtered&filter_name=waiting_for_pay",
				"class" => "menu-item",
				"id" => "orders",
			),
			array(
				"text" => "������� ��������",
				"data-url" => SITE_DIR."eshop_app/personal/order/order_list.php?action=get_filtered&filter_name=waiting_for_delivery",
				"class" => "menu-item",
				"id" => "orders",
			),
			array(
				"text" => "���� ������������",
				"class" => "menu-item",
				"data-url" => SITE_DIR."eshop_app/personal/bill/",
				"id" => "user_bill"
			),
		)
	);
else
	$arMobileMenuItems[] = array(
		"type" => "section",
		"text" =>"��� ������",
		"sort" => "300",
		"items" =>	array(
			array(
				"text" => "������ �������",
				"data-url" => SITE_DIR."eshop_app/personal/order/order_list.php",
				"class" => "menu-item",
				"id" => "orders",
			),
			array(
				"text" => "������� ������",
				"data-url" => SITE_DIR."eshop_app/personal/order/order_list.php?action=get_filtered&filter_name=waiting_for_pay",
				"class" => "menu-item",
				"id" => "orders",
			),
			array(
				"text" => "������� ��������",
				"data-url" => SITE_DIR."eshop_app/personal/order/order_list.php?action=get_filtered&filter_name=waiting_for_delivery",
				"class" => "menu-item",
				"id" => "orders",
			),
		)
	);
?>
<script>
	function openSectionList()
	{
		app.closeMenu();
		app.openBXTable({
			url: '<?=SITE_DIR?>eshop_app/catalog/sections.php',
			isroot: true,
			TABLE_SETTINGS : {
				cache : true,
				use_sections : true,
				searchField : false,
				showtitle : true,
				name : "�������",
				button:
				{
					type:    'basket',
					style:   'custom',
					callback: function()
					{
						app.openNewPage("<?=SITE_DIR?>eshop_app/personal/cart/");
					}
				}
			}
		});
	}
</script>