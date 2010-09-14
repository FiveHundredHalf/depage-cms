<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [ ]>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:rpc="http://cms.depagecms.net/ns/rpc" xmlns:db="http://cms.depagecms.net/ns/database" xmlns:proj="http://cms.depagecms.net/ns/project" xmlns:pg="http://cms.depagecms.net/ns/page" xmlns:sec="http://cms.depagecms.net/ns/section" xmlns:edit="http://cms.depagecms.net/ns/edit" xmlns:backup="http://cms.depagecms.net/ns/backup" version="1.0" extension-element-prefixes="xsl rpc db proj pg sec edit backup ">

    <!-- {{{ root -->
    <xsl:template match="/">
        <feed>
            <xsl:attribute name="xmlns">http://www.w3.org/2005/Atom</xsl:attribute>
            <xsl:call-template name="init-feed" />
            <xsl:for-each select="document('get:navigation')/proj:pages_struct//*[@nav_atom = 'true']/descendant-or-self::pg:page[not(@nav_hidden = 'true')]">
                <xsl:if test="position() &lt; $num_items">
                    <xsl:variable name="url" select="@url" />
                    <xsl:variable name="pageid" select="@db:id" />

                    <xsl:for-each select="document(concat('get:page','/',@db:id))//pg:page_data//*[name() = $entries]">
                        <xsl:if test="position() &lt; $num_items">
                            <entry>
                                <xsl:call-template name="entry">
                                    <xsl:with-param name="anchor" select="concat('#entry-',@db:id)" />
                                    <xsl:with-param name="pageid" select="$pageid" />
                                </xsl:call-template>
                            </entry>
                        </xsl:if>
                    </xsl:for-each>
                </xsl:if>
            </xsl:for-each>
        </feed>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ init-feed -->
    <xsl:template name="init-feed">
        <title><xsl:value-of select="$title" /></title>

        <link><xsl:attribute name="href"><xsl:value-of select="$baseurl" /></xsl:attribute></link>
        <link rel="self"><xsl:attribute name="href"><xsl:value-of select="concat($baseurl,$tt_lang,'/atom.xml')" /></xsl:attribute></link>

        <id><xsl:value-of select="$baseurl" /></id>
        <updated><xsl:value-of select="document('call:formatdate////Y-m-d\TH:i:s\Z')" /></updated>
        <author>
            <name><xsl:value-of select="$author" /></name>
        </author>
        <rights><xsl:value-of select="$rights" /></rights>
        <xsl:if test="$icon != ''">
            <icon><xsl:value-of select="concat($baseurl,$icon)" /></icon>
        </xsl:if>
        <xsl:if test="$logo != ''">
            <logo><xsl:value-of select="concat($baseurl,$logo)" /></logo>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ entry -->
    <xsl:template name="entry">
        <xsl:param name="anchor" />
        <xsl:param name="pageid" />

        <link><xsl:attribute name="href"><xsl:value-of select="$baseurl" /><xsl:value-of select="document(concat('pageref:',$pageid,'/',$tt_lang))" /><xsl:value-of select="$anchor" /></xsl:attribute></link>
        <id><xsl:value-of select="$baseurl" /><xsl:value-of select="document(concat('pageref:',$pageid,'/',$tt_lang))" /><xsl:value-of select="$anchor" /></id>
        <updated><xsl:value-of select="document(concat('call:formatdate/',edit:date/@value,'/','Y-m-d\TH:i:s\Z'))" /></updated>
        <title><xsl:value-of select="edit:text_headline[@lang = $tt_lang]/*" /></title>
        <summary><xsl:value-of select=".//edit:text_formatted[@lang = $tt_lang and 1]/*" /></summary>
        <content type="xhtml">
            <div>
                <xsl:attribute name="xmlns">http://www.w3.org/1999/xhtml</xsl:attribute>
                <xsl:call-template name="content" />
            </div>
        </content>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ edit:text_formatted -->
    <xsl:template match="edit:text_formatted">
        <xsl:if test="@lang = $tt_lang">
            <xsl:apply-templates />
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:text_headline -->
    <xsl:template match="edit:text_headline">
        <xsl:if test="@lang = $tt_lang and count(p) &gt; 0">
            <h1>
                <xsl:for-each select="p">
                    <xsl:apply-templates />
                </xsl:for-each>
            </h1>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ edit:img -->
    <xsl:template match="edit:img">
        <xsl:if test="@src != ''">
            <img>
                <xsl:attribute name="src">
                    <xsl:value-of select="$baseurl" />lib<xsl:value-of select="substring(@src,8)"/>
                </xsl:attribute>
                <xsl:attribute name="width"><xsl:value-of select="document(concat('call:fileinfo/', @src))/file/@width"/></xsl:attribute>
                <xsl:attribute name="height"><xsl:value-of select="document(concat('call:fileinfo/', @src))/file/@height"/></xsl:attribute>
            </img>
        </xsl:if>
    </xsl:template>
    <!-- }}} -->

    <!-- {{{ p -->
    <xsl:template match="p">
        <p><xsl:apply-templates /></p>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ a -->
    <xsl:template match="a">
        <a>
            <xsl:choose>
                <xsl:when test="@href and substring(@href, 1, 8) = 'libref:/'">
                    <xsl:attribute name="href">
                        <xsl:value-of select="$baseurl" />lib<xsl:value-of select="substring(@href,8)" disable-output-escaping="yes" />
                    </xsl:attribute>
                </xsl:when>
                <xsl:when test="@href and not(substring(@href, 1, 8) = 'pageref:')">
                    <xsl:attribute name="href">
                        <xsl:value-of select="@href" disable-output-escaping="yes" />
                    </xsl:attribute>
                </xsl:when>
                <xsl:otherwise>
                    <xsl:attribute name="href">
                        <xsl:value-of select="$baseurl" /><xsl:value-of select="document(concat('pageref:/', @href_id, '/', $tt_lang))/." disable-output-escaping="yes" />
                    </xsl:attribute>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:apply-templates />
            <xsl:text> </xsl:text>
        </a>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ b -->
    <xsl:template match="b">
        <b>
            <xsl:apply-templates />
            <xsl:text> </xsl:text>
        </b>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ i -->
    <xsl:template match="i">
        <i>
            <xsl:apply-templates />
            <xsl:text> </xsl:text>
        </i>
    </xsl:template>
    <!-- }}} -->
    <!-- {{{ small -->
    <xsl:template match="small">
        <small>
            <xsl:apply-templates />
            <xsl:text> </xsl:text>
        </small>
    </xsl:template>
    <!-- }}} -->

    <!-- vim:set ft=xml sw=4 sts=4 fdm=marker : -->
</xsl:stylesheet>

