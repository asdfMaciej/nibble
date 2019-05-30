3 najczęściej kupowane produkty:<br>
<?php foreach ($products as $product): ?>
<div>
	<a href="/shop/product/{{product->slug}}">{{product->name}}, {{product->purchase_count}} razy</a>
</div>
<?php endforeach ?>