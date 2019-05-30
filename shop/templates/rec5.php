Pary według algorytmu Apriori:<br>
<?php foreach ($pairs as $p): ?>
<div>
	{{p[2]}} razy ->
	<a href="/shop/product/{{p[0]->slug}}">{{p[0]->name}}</a>
	oraz
	<a href="/shop/product/{{p[1]->slug}}">{{p[1]->name}}</a>
</div>
<?php endforeach ?>