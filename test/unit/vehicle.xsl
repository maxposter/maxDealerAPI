<?xml version="1.0" encoding="Windows-1251"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method = "html" encoding="utf-8"/>
	<xsl:template match="vehicle">
    <xsl:value-of select="mark" />
	</xsl:template>
</xsl:stylesheet>