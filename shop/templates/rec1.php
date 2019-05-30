3 najczęściej oglądane produkty:<br>
<?php foreach ($products as $product): ?>
<div>
	<a href="/shop/product/{{product->slug}}">{{product->name}}, {{product->view_count}} razy</a>
</div>
<?php endforeach ?>