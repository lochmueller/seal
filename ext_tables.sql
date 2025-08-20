#
# Table structure for table 'tx_seal_domain_model_index_default'
#
CREATE TABLE tx_seal_domain_model_index_default (
    FULLTEXT INDEX title (title),
    FULLTEXT INDEX titlecontent (title,content),
);