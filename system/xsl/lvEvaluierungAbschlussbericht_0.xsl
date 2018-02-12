<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet 
	xmlns:fo="http://www.w3.org/1999/XSL/Format" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	version="1.0"
	xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
	xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" 
	xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" 
	xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" 
	xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0"
	xmlns:xlink="http://www.w3.org/1999/xlink" 
	xmlns:dc="http://purl.org/dc/elements/1.1/" 
	xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" 
	xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" 
	xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" 
	xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" 
>
    
	<xsl:output method="xml" version="1.0" indent="yes"/>
	<xsl:template match="abschlussbericht"> 
        
		<office:document-content 
			xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" 
			xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" 
			xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" 
			xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" 
			xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" 
			xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" 
			xmlns:xlink="http://www.w3.org/1999/xlink" 
			xmlns:dc="http://purl.org/dc/elements/1.1/" 
			xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" 
			xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0"
			xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" 
			xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" 
			xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" 
			xmlns:math="http://www.w3.org/1998/Math/MathML" 
			xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" 
			xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" 
			xmlns:ooo="http://openoffice.org/2004/office" 
			xmlns:ooow="http://openoffice.org/2004/writer" 
			xmlns:oooc="http://openoffice.org/2004/calc" 
			xmlns:dom="http://www.w3.org/2001/xml-events" 
			xmlns:xforms="http://www.w3.org/2002/xforms" 
			xmlns:xsd="http://www.w3.org/2001/XMLSchema" 
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
			xmlns:rpt="http://openoffice.org/2005/report" 
			xmlns:of="urn:oasis:names:tc:opendocument:xmlns:of:1.2" 
			xmlns:xhtml="http://www.w3.org/1999/xhtml" 
			xmlns:grddl="http://www.w3.org/2003/g/data-view#" 
			xmlns:officeooo="http://openoffice.org/2009/office" 
			xmlns:tableooo="http://openoffice.org/2009/table" 
			xmlns:drawooo="http://openoffice.org/2010/draw" 
			xmlns:calcext="urn:org:documentfoundation:names:experimental:calc:xmlns:calcext:1.0" 
			xmlns:loext="urn:org:documentfoundation:names:experimental:office:xmlns:loext:1.0" 
			xmlns:field="urn:openoffice:names:experimental:ooo-ms-interop:xmlns:field:1.0" 
			xmlns:formx="urn:openoffice:names:experimental:ooxml-odf-interop:xmlns:form:1.0" 
			xmlns:css3t="http://www.w3.org/TR/css3-text/" 
			office:version="1.2">
			<office:scripts/>
			<office:font-face-decls>
				<style:font-face style:name="Mangal1" svg:font-family="Mangal"/>
				<style:font-face style:name="Liberation Serif" svg:font-family="&apos;Liberation Serif&apos;" style:font-family-generic="roman" style:font-pitch="variable"/>
				<style:font-face style:name="Arial" svg:font-family="Arial" style:font-family-generic="swiss" style:font-pitch="variable"/>
				<style:font-face style:name="Liberation Sans" svg:font-family="&apos;Liberation Sans&apos;" style:font-family-generic="swiss" style:font-pitch="variable"/>
				<style:font-face style:name="Mangal" svg:font-family="Mangal" style:font-family-generic="system" style:font-pitch="variable"/>
				<style:font-face style:name="Microsoft YaHei" svg:font-family="&apos;Microsoft YaHei&apos;" style:font-family-generic="system" style:font-pitch="variable"/>
				<style:font-face style:name="SimSun" svg:font-family="SimSun" style:font-family-generic="system" style:font-pitch="variable"/>
			</office:font-face-decls>
			<office:automatic-styles>
				<style:style style:name="Tabelle1" style:family="table">
					<style:table-properties style:width="16.946cm" fo:margin-left="0.081cm" table:align="left"/>
				</style:style>
				<style:style style:name="Tabelle1.A" style:family="table-column">
					<style:table-column-properties style:column-width="8.206cm"/>
				</style:style>
				<style:style style:name="Tabelle1.B" style:family="table-column">
					<style:table-column-properties style:column-width="8.74cm"/>
				</style:style>
				<style:style style:name="Tabelle1.A1" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="0.05pt solid #b2b2b2" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle1.B1" style:family="table-cell">
					<style:table-cell-properties style:vertical-align="middle" fo:padding="0.097cm" fo:border-left="none" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="0.05pt solid #b2b2b2" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle1.A2" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle1.B2" style:family="table-cell">
					<style:table-cell-properties style:vertical-align="middle" fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle1.A3" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle1.A4" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle2" style:family="table">
					<style:table-properties style:width="16.946cm" fo:margin-left="0.081cm" table:align="left"/>
				</style:style>
				<style:style style:name="Tabelle2.A" style:family="table-column">
			<style:table-column-properties style:column-width="12.695cm"/>
				</style:style>
		<style:style style:name="Tabelle2.B" style:family="table-column">
			<style:table-column-properties style:column-width="4.251cm"/>
		</style:style>
				<style:style style:name="Tabelle2.A1" style:family="table-cell">
			<style:table-cell-properties fo:background-color="#eeeeee" fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="0.05pt solid #b2b2b2" fo:border-bottom="0.05pt solid #b2b2b2">
				<style:background-image/>
			</style:table-cell-properties>
		</style:style>
		<style:style style:name="Tabelle2.B1" style:family="table-cell">
					<style:table-cell-properties fo:background-color="#eeeeee" fo:padding="0.097cm" fo:border="0.05pt solid #b2b2b2">
						<style:background-image/>
					</style:table-cell-properties>
				</style:style>
				<style:style style:name="Tabelle2.A2" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B2" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle2.A3" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B3" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle2.A4" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B4" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle2.A5" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
		<style:style style:name="Tabelle2.B5" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
				<style:style style:name="Tabelle2.A6" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B6" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle2.A7" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B7" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
		<style:style style:name="Tabelle2.A8" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B8" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A9" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B9" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A10" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B10" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A11" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B11" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A12" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B12" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A13" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B13" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A14" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B14" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A15" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B15" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A16" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B16" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A17" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B17" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A18" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B18" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A19" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B19" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A20" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B20" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.A21" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="none" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
		<style:style style:name="Tabelle2.B21" style:family="table-cell">
			<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
		</style:style>
				<style:style style:name="Tabelle3" style:family="table">
					<style:table-properties style:width="16.946cm" fo:margin-left="0.081cm" table:align="left"/>
				</style:style>
				<style:style style:name="Tabelle3.A" style:family="table-column">
					<style:table-column-properties style:column-width="16.946cm"/>
				</style:style>
				<style:style style:name="Tabelle3.A1" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle3.A2" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle4" style:family="table">
					<style:table-properties style:width="16.946cm" fo:margin-left="0.081cm" table:align="left"/>
				</style:style>
				<style:style style:name="Tabelle4.A" style:family="table-column">
					<style:table-column-properties style:column-width="16.946cm"/>
				</style:style>
				<style:style style:name="Tabelle4.A1" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="Tabelle4.A2" style:family="table-cell">
					<style:table-cell-properties fo:padding="0.097cm" fo:border-left="0.05pt solid #b2b2b2" fo:border-right="0.05pt solid #b2b2b2" fo:border-top="none" fo:border-bottom="0.05pt solid #b2b2b2"/>
				</style:style>
				<style:style style:name="P1" style:family="paragraph" style:parent-style-name="Header">
					<style:text-properties officeooo:rsid="001d5274" officeooo:paragraph-rsid="001d5274"/>
				</style:style>
				<style:style style:name="P2" style:family="paragraph" style:parent-style-name="Standard">
					<style:text-properties style:font-name="Arial" fo:font-size="10pt" officeooo:rsid="00085c86" officeooo:paragraph-rsid="00085c86" style:font-size-asian="8.75pt" style:font-size-complex="10pt"/>
				</style:style>
				<style:style style:name="P3" style:family="paragraph" style:parent-style-name="Standard">
					<style:text-properties style:font-name="Arial" fo:font-size="10pt" officeooo:rsid="00085c86" officeooo:paragraph-rsid="002126bf" style:font-size-asian="8.75pt" style:font-size-complex="10pt"/>
				</style:style>
				<style:style style:name="P4" style:family="paragraph" style:parent-style-name="Standard">
					<style:text-properties style:font-name="Arial" fo:font-size="10pt" officeooo:rsid="000a058f" officeooo:paragraph-rsid="000a058f" style:font-size-asian="8.75pt" style:font-size-complex="10pt"/>
				</style:style>
				<style:style style:name="P5" style:family="paragraph" style:parent-style-name="Standard">
					<style:text-properties style:font-name="Arial" fo:font-size="10pt" officeooo:rsid="000a058f" officeooo:paragraph-rsid="00085c86" style:font-size-asian="8.75pt" style:font-size-complex="10pt"/>
				</style:style>
				<style:style style:name="P6" style:family="paragraph" style:parent-style-name="Standard">
					<style:text-properties style:font-name="Arial" fo:font-size="10pt" officeooo:rsid="000a058f" officeooo:paragraph-rsid="000f7747" style:font-size-asian="8.75pt" style:font-size-complex="10pt"/>
				</style:style>
				<style:style style:name="P7" style:family="paragraph" style:parent-style-name="Standard">
					<style:text-properties style:font-name="Arial" fo:font-size="10pt" officeooo:rsid="000a058f" officeooo:paragraph-rsid="0023ed3c" style:font-size-asian="8.75pt" style:font-size-complex="10pt"/>
				</style:style>
				<style:style style:name="P8" style:family="paragraph" style:parent-style-name="Standard">
					<style:text-properties style:font-name="Arial" fo:font-size="10pt" officeooo:rsid="000a058f" officeooo:paragraph-rsid="00254735" style:font-size-asian="8.75pt" style:font-size-complex="10pt"/>
				</style:style>
				<style:style style:name="P9" style:family="paragraph" style:parent-style-name="Standard">
					<style:text-properties style:font-name="Arial" fo:font-size="10pt" officeooo:rsid="0017ca52" officeooo:paragraph-rsid="000e668c" style:font-size-asian="8.75pt" style:font-size-complex="10pt"/>
				</style:style>
				<style:style style:name="P10" style:family="paragraph" style:parent-style-name="Standard">
					<style:text-properties style:font-name="Arial" fo:font-size="10pt" fo:font-weight="bold" officeooo:rsid="0023ed3c" officeooo:paragraph-rsid="00254735" style:font-size-asian="8.75pt" style:font-weight-asian="bold" style:font-size-complex="10pt" style:font-weight-complex="bold"/>
				</style:style>
				<style:style style:name="P11" style:family="paragraph" style:parent-style-name="Standard">
					<style:text-properties style:font-name="Arial" fo:font-size="14pt" officeooo:rsid="00229b7b" officeooo:paragraph-rsid="00085c86" style:font-size-asian="14pt" style:font-size-complex="14pt"/>
				</style:style>
				<style:style style:name="P12" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:text-align="center" style:justify-single-word="false"/>
					<style:text-properties style:font-name="Arial" fo:font-size="14pt" officeooo:rsid="00229b7b" officeooo:paragraph-rsid="002a590f" style:font-size-asian="14pt" style:font-size-complex="14pt"/>
				</style:style>
				<style:style style:name="P13" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:text-align="start" style:justify-single-word="false"/>
					<style:text-properties style:font-name="Arial" fo:font-size="14pt" officeooo:rsid="00229b7b" officeooo:paragraph-rsid="002f3eaf" style:font-size-asian="14pt" style:font-size-complex="14pt"/>
				</style:style>
				<style:style style:name="P14" style:family="paragraph" style:parent-style-name="Standard">
					<style:paragraph-properties fo:text-align="center" style:justify-single-word="false"/>
					<style:text-properties style:font-name="Arial" fo:font-size="16pt" officeooo:rsid="00229b7b" officeooo:paragraph-rsid="002a590f" style:font-size-asian="16pt" style:font-size-complex="16pt"/>
				</style:style>
		<style:style style:name="P15" style:family="paragraph" style:parent-style-name="Standard">
			<style:paragraph-properties fo:text-align="center" style:justify-single-word="false"/>
			<style:text-properties style:font-name="Arial" fo:font-size="16pt" officeooo:rsid="002a590f" officeooo:paragraph-rsid="002a590f" style:font-size-asian="16pt" style:font-size-complex="16pt"/>
				</style:style>
				<style:style style:name="P16" style:family="paragraph" style:parent-style-name="Table_20_Contents">
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" fo:font-weight="bold" officeooo:rsid="0023ed3c" officeooo:paragraph-rsid="0023ed3c" style:font-size-asian="8.75pt" style:font-weight-asian="bold" style:font-size-complex="10pt" style:font-weight-complex="bold"/>
				</style:style>
				<style:style style:name="P17" style:family="paragraph" style:parent-style-name="Table_20_Contents">
			<style:paragraph-properties fo:text-align="start" style:justify-single-word="false"/>
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" fo:font-weight="bold" officeooo:rsid="00352700" officeooo:paragraph-rsid="00352700" style:font-size-asian="8.75pt" style:font-weight-asian="bold" style:font-size-complex="10pt" style:font-weight-complex="bold"/>
				</style:style>
				<style:style style:name="P18" style:family="paragraph" style:parent-style-name="Table_20_Contents">
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" fo:font-weight="normal" officeooo:rsid="0023ed3c" officeooo:paragraph-rsid="0023ed3c" style:font-size-asian="8.75pt" style:font-weight-asian="normal" style:font-size-complex="10pt" style:font-weight-complex="normal"/>
				</style:style>
				<style:style style:name="P19" style:family="paragraph" style:parent-style-name="Table_20_Contents">
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" fo:font-weight="normal" officeooo:rsid="00254735" officeooo:paragraph-rsid="00254735" style:font-size-asian="8.75pt" style:font-weight-asian="normal" style:font-size-complex="10pt" style:font-weight-complex="normal"/>
		</style:style>
		<style:style style:name="P20" style:family="paragraph" style:parent-style-name="Table_20_Contents">
					<style:paragraph-properties fo:margin-left="0.499cm" fo:margin-right="0cm" fo:text-indent="0cm" style:auto-text-indent="false" style:writing-mode="page"/>
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" officeooo:rsid="0023ed3c" officeooo:paragraph-rsid="0023ed3c" style:font-size-asian="8.75pt" style:font-size-complex="10pt"/>
				</style:style>
				<style:style style:name="P21" style:family="paragraph" style:parent-style-name="Table_20_Contents">
			<style:paragraph-properties fo:margin-left="0.499cm" fo:margin-right="0cm" fo:text-indent="0cm" style:auto-text-indent="false" style:writing-mode="page"/>
					<style:text-properties style:font-name="Arial" fo:font-size="10pt" fo:font-weight="bold" officeooo:rsid="0023ed3c" officeooo:paragraph-rsid="0023ed3c" style:font-size-asian="8.75pt" style:font-weight-asian="bold" style:font-size-complex="10pt" style:font-weight-complex="bold"/>
				</style:style>
				<style:style style:name="P22" style:family="paragraph" style:parent-style-name="Table_20_Contents">
			<style:paragraph-properties fo:text-align="start" style:justify-single-word="false"/>
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" fo:font-weight="bold" officeooo:rsid="00351978" officeooo:paragraph-rsid="00351978" style:font-size-asian="8.75pt" style:font-weight-asian="bold" style:font-size-complex="10pt" style:font-weight-complex="bold"/>
				</style:style>
				<style:style style:name="P23" style:family="paragraph" style:parent-style-name="Table_20_Contents">
			<style:paragraph-properties fo:text-align="center" style:justify-single-word="false"/>
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" fo:font-weight="normal" officeooo:rsid="0023ed3c" officeooo:paragraph-rsid="0023ed3c" style:font-size-asian="8.75pt" style:font-weight-asian="normal" style:font-size-complex="10pt" style:font-weight-complex="normal"/>
				</style:style>
				<style:style style:name="P24" style:family="paragraph" style:parent-style-name="Table_20_Contents">
			<style:paragraph-properties fo:text-align="center" style:justify-single-word="false"/>
			<style:text-properties style:font-name="Arial" fo:font-size="10pt" fo:font-weight="normal" officeooo:rsid="00351978" officeooo:paragraph-rsid="00351978" style:font-size-asian="8.75pt" style:font-weight-asian="normal" style:font-size-complex="10pt" style:font-weight-complex="normal"/>
				</style:style>
				<style:style style:name="T1" style:family="text">
					<style:text-properties style:font-name="Arial" fo:font-size="8pt" style:font-size-asian="8pt" style:font-size-complex="8pt"/>
				</style:style>
				<style:style style:name="T2" style:family="text">
					<style:text-properties style:font-name="Arial" fo:font-size="8pt" officeooo:rsid="000bc4a4" style:font-size-asian="8pt" style:font-size-complex="8pt"/>
				</style:style>
				<style:style style:name="T3" style:family="text">
					<style:text-properties officeooo:rsid="0023ed3c"/>
				</style:style>
				<style:style style:name="fr1" style:family="graphic" style:parent-style-name="Graphics">
					<style:graphic-properties style:vertical-pos="from-top" style:vertical-rel="page" style:horizontal-pos="from-left" style:horizontal-rel="page" style:mirror="none" fo:clip="rect(0cm, 0cm, 0cm, 0cm)" draw:luminance="0%" draw:contrast="0%" draw:red="0%" draw:green="0%" draw:blue="0%" draw:gamma="100%" draw:color-inversion="false" draw:image-opacity="100%" draw:color-mode="standard"/>
				</style:style>
			</office:automatic-styles>
			<office:body>
				<office:text text:use-soft-page-breaks="true">
					<text:sequence-decls>
						<text:sequence-decl text:display-outline-level="0" text:name="Illustration"/>
						<text:sequence-decl text:display-outline-level="0" text:name="Table"/>
						<text:sequence-decl text:display-outline-level="0" text:name="Text"/>
						<text:sequence-decl text:display-outline-level="0" text:name="Drawing"/>
					</text:sequence-decls>
					<draw:frame draw:style-name="fr1" draw:name="Bild1" text:anchor-type="page" text:anchor-page-number="1" svg:x="14.81cm" svg:y="2.939cm" svg:width="4.2cm" svg:height="2.24cm" draw:z-index="0">
						<draw:image xlink:href="Pictures/10000201000000FD00000082B4907F4517CEC4AC.png" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/>
					</draw:frame>
					<text:p text:style-name="P13">Lehrveranstaltungsevaluierung</text:p>
					<text:p text:style-name="P13">Studienjahr-Abschlussbericht</text:p>
					<text:p text:style-name="P5"/>
					<text:p text:style-name="P9"/>
					<text:p text:style-name="P3"/>
					<text:p text:style-name="P11"/>
					<text:p text:style-name="P14"/>
					<text:p text:style-name="P15">Studienjahr-Abschlussbericht <xsl:value-of select="studiengang_kurz" /> - <xsl:value-of select="studienjahr" /></text:p>
					<text:p text:style-name="P12"/>
					<text:p text:style-name="P2"/>
					<table:table table:name="Tabelle1" table:style-name="Tabelle1">
						<table:table-column table:style-name="Tabelle1.A"/>
						<table:table-column table:style-name="Tabelle1.B"/>
						<table:table-row>
							<table:table-cell table:style-name="Tabelle1.A1" office:value-type="string">
								<text:p text:style-name="P16">Studiengang</text:p>
							</table:table-cell>
							<table:table-cell table:style-name="Tabelle1.B1" office:value-type="string">
								<text:p text:style-name="P21">
									<xsl:value-of select="studiengang_lang" />
								</text:p>
							</table:table-cell>
						</table:table-row>
						<table:table-row>
							<table:table-cell table:style-name="Tabelle1.A2" office:value-type="string">
								<text:p text:style-name="P16">Studienjahr</text:p>
							</table:table-cell>
							<table:table-cell table:style-name="Tabelle1.B2" office:value-type="string">
								<text:p text:style-name="P21">
									<xsl:value-of select="wintersemester" /> / <xsl:value-of select="sommersemester" />
								</text:p>
							</table:table-cell>
						</table:table-row>
						<table:table-row>
							<table:table-cell table:style-name="Tabelle1.A3" office:value-type="string">
								<text:p text:style-name="P18">Anzahl der evaluierten Lehrveranstaltungen</text:p>
							</table:table-cell>
							<table:table-cell table:style-name="Tabelle1.B2" office:value-type="string">
								<text:p text:style-name="P20">Von <xsl:value-of select="anzahl_lvs" /> angebotenen Lehrveranstaltungen wurden <xsl:value-of select="anzahl_evaluierte_lvs" /> evauliert.</text:p>
							</table:table-cell>
						</table:table-row>
						<table:table-row>
							<table:table-cell table:style-name="Tabelle1.A4" office:value-type="string">
								<text:p text:style-name="P18">Evaluationsquote</text:p>
							</table:table-cell>
							<table:table-cell table:style-name="Tabelle1.B2" office:value-type="string">
								<text:p text:style-name="P20"><xsl:value-of select="evaluationsquote" /></text:p>
							</table:table-cell>
						</table:table-row>
					</table:table>
					<text:p text:style-name="P2"/>
					<text:p text:style-name="P6"/>
					<text:p text:style-name="P7"/>
					<table:table table:name="Tabelle2" table:style-name="Tabelle2">
						<table:table-column table:style-name="Tabelle2.A"/>
						<table:table-column table:style-name="Tabelle2.B"/>					

						<xsl:apply-templates select="evaluierte_lehrveranstaltungen"/>
												
					</table:table>
					<text:p text:style-name="P7"/>
					<text:p text:style-name="P4"/>
					<text:p text:style-name="P7"/>
					<table:table table:name="Tabelle3" table:style-name="Tabelle3">
						<table:table-column table:style-name="Tabelle3.A"/>
						<table:table-row>
							<table:table-cell table:style-name="Tabelle3.A1" office:value-type="string">
								<text:p text:style-name="P10">Welche Ergebnisse gibt es?</text:p>
							</table:table-cell>
						</table:table-row>
						<text:soft-page-break/>
						<table:table-row>
							<table:table-cell table:style-name="Tabelle3.A2" office:value-type="string">
								<text:p text:style-name="P19">
									<xsl:value-of select="ergebnisse" />
								</text:p>
								<text:p text:style-name="P19"/>
							</table:table-cell>
						</table:table-row>
					</table:table>
					<text:p text:style-name="P7"/>
					<text:p text:style-name="P4"/>
					<text:p text:style-name="P8"/>
					<table:table table:name="Tabelle4" table:style-name="Tabelle4">
						<table:table-column table:style-name="Tabelle4.A"/>
						<table:table-row>
							<table:table-cell table:style-name="Tabelle4.A1" office:value-type="string">
								<text:p text:style-name="P10">Welche Verbesserungsmaßnahmen wurden gesetzt?</text:p>
							</table:table-cell>
						</table:table-row>
						<table:table-row>
							<table:table-cell table:style-name="Tabelle4.A2" office:value-type="string">
								<text:p text:style-name="P19">
									<xsl:value-of select="verbesserungen" />
								</text:p>
								<text:p text:style-name="P19"/>
							</table:table-cell>
						</table:table-row>
					</table:table>
					<text:p text:style-name="P8"/>
					<text:p text:style-name="P4">
				<text:s/>
					</text:p>
					<text:p text:style-name="P4"/>
				</office:text>
			</office:body>
		</office:document-content>
	</xsl:template>


	<xsl:template match="evaluierte_lehrveranstaltungen">     
		<!--graue überschrift-zeile (pro organisationsform)-->
		<table:table-row>
			<table:table-cell table:style-name="Tabelle2.A1" office:value-type="string">
				<text:p text:style-name="P17">
					<text:span text:style-name="T3">Evaluierte Lehrveranstaltungen </text:span>  <xsl:value-of select="org_form" /></text:p>
			</table:table-cell>
			<table:table-cell table:style-name="Tabelle2.B1" office:value-type="string">
						<text:p text:style-name="P22">Ausbildungssemester</text:p>
			</table:table-cell>
		</table:table-row>
		<xsl:if test="evaluierte_lvs != ''"> 
			<xsl:apply-templates select="evaluierte_lvs"/>
		</xsl:if>
		<xsl:if test="evaluierte_lvs = ''"> 
			<table:table-row>
				<table:table-cell table:style-name="Tabelle2.A2" office:value-type="string">
					<text:p text:style-name="P18">Es gibt keine evaluierten Lehrveranstaltungen in diesem Studienjahr.</text:p>
				</table:table-cell>
				<table:table-cell table:style-name="Tabelle2.B2" office:value-type="string">
				</table:table-cell>
			</table:table-row>
		</xsl:if>
	</xsl:template>
	
	<!--zeilen lehrveranstaltungen-->
	<xsl:template match="evaluierte_lvs">  
			<xsl:apply-templates select="evaluierte_lvs_zeile"/>
	</xsl:template>
	
	<xsl:template match="evaluierte_lvs_zeile">  
		<table:table-row>
			<table:table-cell table:style-name="Tabelle2.A2" office:value-type="string">
				<text:p text:style-name="P18"><xsl:value-of select="lv" /></text:p>
			</table:table-cell>
			<table:table-cell table:style-name="Tabelle2.B2" office:value-type="string">
				<text:p text:style-name="P24"><xsl:value-of select="semester" /></text:p>
			</table:table-cell>
		</table:table-row>	
	</xsl:template>	
		
</xsl:stylesheet>
