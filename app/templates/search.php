<div class="row">
	<div class="col-md-12">
		<h3 class="page-header">Search Results <small><?= $data['search_count']; ?> Total Results</small></h3>
		<table class="table table-hover" role="table">
			<thead>
				<tr>
					<th>Username</th>
					<th># of Retweets</th>
				</tr>
			</thead>
			<tbody>
				<?= $data['search_results']; ?>
			</tbody>
		</table>
	</div>
</div>
