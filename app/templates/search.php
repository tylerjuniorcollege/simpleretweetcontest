<div class="row">
	<div class="col-md-12">
		<h3 class="page-header">Search Results <small><?= $data['search_count']; ?> Total Results</small></h3>
		<table class="table table-hover" role="table">
			<thead>
				<tr>
					<th>Username</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($data['search_results'] as $result) {
					printf('<tr><td><a href="/user/%s">%s</a></td></tr>', $result->id, $result->username);
				} ?>
			</tbody>
		</table>
	</div>
</div>
