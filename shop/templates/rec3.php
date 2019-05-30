3 ostatnio obejrzane produkty:<br>
<?php foreach ($products as $product): ?>
<div>
	<a href="/shop/product/{{product->slug}}">{{product->name}}, {{product->view_date}}</a>
</div>
<?php endforeach ?>