package productcatalog

import productcatalogusecase "pos-go/internal/modules/productcatalog/usecase"

type ProductLookupResponse struct {
	ID              string  `json:"id"`
	Code            *string `json:"kode_barang"`
	Name            string  `json:"nama_barang"`
	Brand           string  `json:"merek"`
	Size            *int    `json:"ukuran"`
	SalePriceRupiah int64   `json:"harga_jual"`
	Status          string  `json:"status"`
}

func FromProductLookup(result productcatalogusecase.LookupProductsResult) []ProductLookupResponse {
	responses := make([]ProductLookupResponse, 0, len(result.Items))
	for _, item := range result.Items {
		responses = append(responses, ProductLookupResponse{
			ID:              item.ID,
			Code:            item.Code,
			Name:            item.Name,
			Brand:           item.Brand,
			Size:            item.Size,
			SalePriceRupiah: item.SalePriceRupiah,
			Status:          item.Status,
		})
	}

	return responses
}
