
#
# Table structure for table 'tx_seal_domain_model_index_page'
#
CREATE TABLE tx_seal_domain_model_index_page (
    FULLTEXT INDEX title (title),
    FULLTEXT INDEX titlecontent (title,content),
);
#
# Table structure for table 'tx_seal_domain_model_index_document'
#
CREATE TABLE tx_seal_domain_model_index_document (
    FULLTEXT INDEX title (title),
    FULLTEXT INDEX titlecontent (title,content),
);
