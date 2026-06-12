package productcatalog

import (
	"time"

	productcatalogusecase "pos-go/internal/modules/productcatalog/usecase"
)

type ProductListItemResponse struct {
	ID              string  `json:"id"`
	Code            *string `json:"kode_barang"`
	Name            string  `json:"nama_barang"`
	Brand           string  `json:"merek"`
	Size            *int    `json:"ukuran"`
	SalePriceRupiah int64   `json:"harga_jual"`
	Status          string  `json:"status"`
}

type ProductDetailResponse struct {
	ID                   string  `json:"id"`
	Code                 *string `json:"kode_barang"`
	Name                 string  `json:"nama_barang"`
	NormalizedName       string  `json:"nama_barang_normalized"`
	Brand                string  `json:"merek"`
	NormalizedBrand      string  `json:"merek_normalized"`
	Size                 *int    `json:"ukuran"`
	SalePriceRupiah      int64   `json:"harga_jual"`
	ReorderPointQty      *int    `json:"reorder_point_qty"`
	CriticalThresholdQty *int    `json:"critical_threshold_qty"`
	Status               string  `json:"status"`
}

type ProductWriteResponse struct {
	ID                   string  `json:"id"`
	Code                 *string `json:"kode_barang"`
	Name                 string  `json:"nama_barang"`
	NormalizedName       string  `json:"nama_barang_normalized"`
	Brand                string  `json:"merek"`
	NormalizedBrand      string  `json:"merek_normalized"`
	Size                 *int    `json:"ukuran"`
	SalePriceRupiah      int64   `json:"harga_jual"`
	ReorderPointQty      *int    `json:"reorder_point_qty"`
	CriticalThresholdQty *int    `json:"critical_threshold_qty"`
	Status               string  `json:"status"`
	CreatedAt            string  `json:"created_at,omitempty"`
	UpdatedAt            string  `json:"updated_at,omitempty"`
	RevisionNo           int     `json:"revision_no,omitempty"`
}

type ProductLifecycleResponse struct {
	ID         string `json:"id"`
	Status     string `json:"status"`
	DeletedAt  string `json:"deleted_at,omitempty"`
	RestoredAt string `json:"restored_at,omitempty"`
	RevisionNo int    `json:"revision_no"`
}

func FromProductList(result productcatalogusecase.ListProductsResult) []ProductListItemResponse {
	responses := make([]ProductListItemResponse, 0, len(result.Items))
	for _, item := range result.Items {
		responses = append(responses, ProductListItemResponse{
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

func FromProductDetail(result productcatalogusecase.GetProductDetailResult) ProductDetailResponse {
	return ProductDetailResponse{
		ID:                   result.ID,
		Code:                 result.Code,
		Name:                 result.Name,
		NormalizedName:       result.NormalizedName,
		Brand:                result.Brand,
		NormalizedBrand:      result.NormalizedBrand,
		Size:                 result.Size,
		SalePriceRupiah:      result.SalePriceRupiah,
		ReorderPointQty:      result.ReorderPointQty,
		CriticalThresholdQty: result.CriticalThresholdQty,
		Status:               result.Status,
	}
}

func FromCreatedProduct(result productcatalogusecase.CreateProductResult) ProductWriteResponse {
	return ProductWriteResponse{
		ID:                   result.ID,
		Code:                 result.Code,
		Name:                 result.Name,
		NormalizedName:       result.NormalizedName,
		Brand:                result.Brand,
		NormalizedBrand:      result.NormalizedBrand,
		Size:                 result.Size,
		SalePriceRupiah:      result.SalePriceRupiah,
		ReorderPointQty:      result.ReorderPointQty,
		CriticalThresholdQty: result.CriticalThresholdQty,
		Status:               result.Status,
		CreatedAt:            formatRFC3339(result.CreatedAt),
		UpdatedAt:            formatRFC3339(result.UpdatedAt),
	}
}

func FromUpdatedProduct(result productcatalogusecase.UpdateProductResult) ProductWriteResponse {
	return ProductWriteResponse{
		ID:                   result.ID,
		Code:                 result.Code,
		Name:                 result.Name,
		NormalizedName:       result.NormalizedName,
		Brand:                result.Brand,
		NormalizedBrand:      result.NormalizedBrand,
		Size:                 result.Size,
		SalePriceRupiah:      result.SalePriceRupiah,
		ReorderPointQty:      result.ReorderPointQty,
		CriticalThresholdQty: result.CriticalThresholdQty,
		Status:               result.Status,
		UpdatedAt:            formatRFC3339(result.UpdatedAt),
		RevisionNo:           result.RevisionNo,
	}
}

func FromDeletedProduct(result productcatalogusecase.SoftDeleteProductResult) ProductLifecycleResponse {
	return ProductLifecycleResponse{
		ID:         result.ID,
		Status:     result.Status,
		DeletedAt:  formatRFC3339(result.DeletedAt),
		RevisionNo: result.RevisionNo,
	}
}

func FromRestoredProduct(result productcatalogusecase.RestoreProductResult) ProductLifecycleResponse {
	return ProductLifecycleResponse{
		ID:         result.ID,
		Status:     result.Status,
		RestoredAt: formatRFC3339(result.RestoredAt),
		RevisionNo: result.RevisionNo,
	}
}

func formatRFC3339(t time.Time) string {
	if t.IsZero() {
		return ""
	}

	return t.UTC().Format(time.RFC3339)
}
