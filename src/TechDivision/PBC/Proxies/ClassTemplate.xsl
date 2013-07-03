<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:str="http://xsltsl.org/string"
                xmlns:php="http://php.net/xsl">

    <xsl:output encoding="UTF-8" method="text"/>

    <xsl:template match="class">
        <xsl:text disable-output-escaping="yes">&lt;?php</xsl:text>

        class <xsl:value-of select="@className"/>
        extends <xsl:value-of select="@proxiedClassName"/>
        {

        /**
        * Will hold an instance of parent.
        * @var mixed
        */
        private $PBCOld;

        /**
        * This is the invariant check for this certain class.
        * It will throw an PBCInvariantBroken exception if the invariant does not hold.
        *
        * @throws PBCInvariantBroken
        */
        private function <xsl:value-of select="@classInvariant"/> ()
        {
        }

        <xsl:if test="methods">
            <xsl:for-each select="methods/method">

            </xsl:for-each>
        </xsl:if>
    </xsl:template>

</xsl:stylesheet>