<?php

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IDatabase;
use MediaWiki\Title\Title;

class SpecialLyricOnlyPages extends SpecialPage {

    private const NS_LYRIC = 3200;

    public function __construct() {
        parent::__construct( 'LyricOnlyPages' );
    }

    public function execute( $subPage ) {
        $this->setHeaders();
        $this->outputHeader();

        $out = $this->getOutput();
        $out->addModules( 'mediawiki.special' );

		$out->addWikiMsg( 'changpopwiki-lyriconlypages-text' );

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );

        // 결과를 보여주는 구조 시작
        $out->addHTML( '<div class="mw-spcontent">' );

        // 결과 테이블 표시
        $lyricOnlyPages = $this->getLyricOnlyPages( $dbr );
        // HTML 대신 위키텍스트를 생성하는 메서드의 결과물을 추가합니다.
        $out->addWikiTextAsInterface( $this->formatResultsAsWikitext( $lyricOnlyPages ) );

        $out->addHTML( '</div>' );
    }

    private function getLyricOnlyPages(IDatabase $dbr ) {
        $result = $dbr->select(
            'page',
            [
                'page_id',
                'page_title',
                'page_len',
                'page_touched'
            ],
            [
                'page_namespace' => self::NS_LYRIC,
                'page_is_redirect' => 0,
                'page_title NOT IN (' . $dbr->buildSelectSubquery(
                    'page',
                    'page_title',
                    [
                        'page_namespace' => NS_MAIN,
                        'page_is_redirect' => 0
                    ]
                ) . ')'
            ],
            __METHOD__,
            [
                'ORDER BY' => 'page_title',
                'LIMIT' => 1000
            ]
        );

        $pages = [];
        foreach ( $result as $row ) {
            $pages[] = [
                'id' => $row->page_id,
                'title' => $row->page_title,
                'length' => $row->page_len,
                'touched' => $row->page_touched
            ];
        }

        return $pages;
    }

    /**
     * 결과를 HTML 대신 위키텍스트로 포맷합니다.
     * @param array $lyricOnlyPages
     * @return string
     */
    private function formatResultsAsWikitext( $lyricOnlyPages ) {
        if ( empty( $lyricOnlyPages ) ) {
            // msg(...)->parse()는 메시지를 위키텍스트로 파싱하여 반환합니다.
            return $this->msg( 'specialpage-empty' )->parse();
        }

        $lang = $this->getLanguage();

        // HTML 테이블 대신 위키텍스트 테이블 문법을 사용합니다.
        $wikitext = '{| class="wikitable sortable"' . "\n";
        $wikitext .= '!' . $this->msg( 'changpopwiki-lyric-page' )->text() . "\n";
        $wikitext .= '!' . $this->msg( 'changpopwiki-page-size' )->text() . "\n";
        $wikitext .= '!' . $this->msg( 'changpopwiki-last-modified' )->text() . "\n";
        $wikitext .= '!' . $this->msg( 'changpopwiki-corresponding-main' )->text() . "\n";

        foreach ( $lyricOnlyPages as $page ) {
            $title = Title::newFromText( $page['title'], self::NS_LYRIC );
            $mainTitle = Title::newFromText( $page['title'], NS_MAIN );

            if ( !$title ) {
                continue;
            }

            $wikitext .= "|-\n"; // 새 행 시작

            // 가사 페이지 링크
            $wikitext .= "| [[" . $title->getPrefixedText() . "]]\n";

            // 페이지 크기
            $wikitext .= "| " . $lang->formatSize( $page['length'] ) . "\n";

            // 마지막 수정 시간
            $wikitext .= "| " . $lang->userTimeAndDate( $page['touched'], $this->getUser() ) . "\n";

            // 대응하는 일반 문서 생성 링크
            $wikitext .= "| ";
            if ( $mainTitle ) {
                $wikitext .= "{{#formlink:form=창팝|target=" . $mainTitle->getPrefixedText() . "|tooltip=양식으로 본 문서 만들기|}}";
            }
            $wikitext .= "\n";
        }

        $wikitext .= "|}\n"; // 테이블 끝

        return $wikitext;
    }

    public function getGroupName() {
        return 'maintenance';
    }

    public function getDescription() {
        return $this->msg( 'changpopwiki-lyriconly-desc' );
    }
}
