Nieobejrzane produkty z kategorii, w których użytkownik był:<br>
<?php foreach ($products as $product): ?>
<div>
	<a href="/shop/product/{{product->slug}}">{{product->name}}</a>
</div>
<?php endforeach ?>