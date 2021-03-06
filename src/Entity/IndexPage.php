<?php

namespace Wikisource\WsContest\Entity;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Wikisource\Api\IndexPage as WikisourceIndexPage;
use Wikisource\Api\WikisourceApi;

class IndexPage extends Model {

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [ 'url' ];

	/** @var WikisourceIndexPage */
	protected $wsIndexPage;

	public function scores() {
		return $this->hasMany( Score::class );
	}

	public function contests() {
		return $this->belongsToMany( Contest::class, 'contest_index_pages' );
	}

	/**
	 * @return WikisourceIndexPage
	 * @throws Exception
	 */
	public function getWikisourceIndexPage() {
		if ( $this->wsIndexPage instanceof \Wikisource\Api\IndexPage ) {
			return $this->wsIndexPage;
		}
		$wikisourceApi = new WikisourceApi();
		$wikisource = $wikisourceApi->newWikisourceFromUrl( $this->url );
		if ( !$wikisource ) {
			throw new Exception( 'Unable to determine Wikisource from URL: ' . $this->url );
		}
		$this->wsIndexPage = $wikisource->getIndexPageFromUrl( $this->url );
		if ( !$this->wsIndexPage->loaded() ) {
			throw new Exception( 'Unable to load Index page from URL: ' . $this->url );
		}
		return $this->wsIndexPage;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function getDomainName() {
		return $this->getWikisourceIndexPage()->getWikisource()->getDomainName();
	}

	/**
	 * An IndexPage needs to be scored if it
	 *   a) has no scores; or
	 *   b) is part of a Contest that is in progress.
	 *
	 * @param Builder $query
	 * @return Builder
	 */
	public function scopeNeedsScoring( Builder $query ) {
		return $query
			->has( 'contests' )
			->doesntHave( 'scores' )
			->orWhereHas( 'contests', function ( Builder $query ) {
				return $query->inProgress();
			} );
	}
}
