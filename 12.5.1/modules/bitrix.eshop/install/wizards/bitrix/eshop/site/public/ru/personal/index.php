<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("������������ ������");
?>
<div class="personal-page-nav">
	<p>� ������ �������� �� ������ ��������� ������� ��������� �������, ��� ���������� ����� �������, ����������� ��� �������� ������ ����������, � ����� ����������� �� ������� � ������ �������������� ��������. </p>
	<div>
		<h2>������ ����������</h2>
		<ul class="lsnn">
			<li><a href="profile/">�������� ��������������� ������</a></li>
		</ul>
	</div>
	<div>
		<h2>������</h2>
		<ul class="lsnn">
			<li><a href="order/">������������ � ���������� �������</a></li>
			<li><a href="cart/">���������� ���������� �������</a></li>
			<li><a href="order/?filter_history=Y">���������� ������� �������</a></li>
		</ul>
	</div>
	<div>
		<h2>��������</h2>
		<ul class="lsnn">
			<li><a href="subscribe/">�������� ��������</a></li>
		</ul>
	</div>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
