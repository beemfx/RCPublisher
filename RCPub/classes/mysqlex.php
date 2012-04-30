<?php
	define('FULL_TITLE_QUERY', 'if(txtSeries is null,
									txtTitle,
									if(fVolume is null and txtTitle is null,
										txtSeries,
									  if(txtTitle is null,
										  concat(txtSeries, " ", fVolume),
										  if(fVolume is null,
											  concat(txtSeries, ": ", txtTitle),
											  concat(txtSeries, " ",fVolume, ": ", txtTitle)
										  )
									  )
									)
								)');



	define('AUTHOR_QUERY', 'concat(txtAuthorFirst, " ", txtAuthorLast)');

	define('DATE_QUERY', 'concat(
			if(nPrDateMonth is not null, concat(monthname(concat("0000-", nPrDateMonth, "-00")), " "), ""),
			if(nPrDateDay is not null, concat(nPrDateDay, ", "), ""),
			if(nPrDateYear is not null, nPrDateYear, ""))');
?>
