<?php

use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Output\OutputPage;

class Hooks implements BeforePageDisplayHook {

    /**
     * BeforePageDisplay hook handler.
     *
     * @param OutputPage $out The OutputPage object.
     * @param \Skin $skin The Skin object.
     */
    public function onBeforePageDisplay( $out, $skin ): void {

        $title = $out->getTitle();
        if ( !$title ) {
            return;
        }

        if (  $title->getNamespace() == NS_LYRIC ) {
			$out->addSubtitle( wfMessage( 'changpopwiki-lyricpage-tomain', "{{#formredlink:form=창팝|target=" .$title->getBaseText() . "|tooltip=양식으로 본 문서 만들기|}}" )->parse() );
        }
    }
}
