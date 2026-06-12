package http

import (
	"bytes"
	"context"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	authdomain "pos-go/internal/modules/auth/domain"
	"pos-go/internal/modules/productcatalog/ports"
	productcatalogusecase "pos-go/internal/modules/productcatalog/usecase"
	httpmw "pos-go/internal/transport/http/middleware"

	"github.com/labstack/echo/v4"
)

func TestProductCatalogHandlerCreateMapsPublicFieldsAndActor(t *testing.T) {
	create := &fakeCreateProduct{}
	handler := NewProductCatalogHandler(nil, nil, nil, create, nil, nil, nil, nil)

	body := []byte(`{
		"kode_barang":"SKU-001",
		"nama_barang":"Kampas Rem",
		"merek":"Honda",
		"ukuran":14,
		"harga_jual":40000,
		"reorder_point_qty":5,
		"critical_threshold_qty":2,
		"reason":"created by API"
	}`)

	req := httptest.NewRequest(http.MethodPost, "/products", bytes.NewReader(body))
	req.Header.Set(echo.HeaderContentType, echo.MIMEApplicationJSON)
	req = req.WithContext(httpmw.WithPrincipal(req.Context(), authdomain.Principal{
		AccountID: "actor-1",
	}))
	rec := httptest.NewRecorder()

	c := echo.New().NewContext(req, rec)

	if err := handler.Create(c); err != nil {
		t.Fatalf("Create() error = %v", err)
	}

	if rec.Code != http.StatusCreated {
		t.Fatalf("status = %d, want %d", rec.Code, http.StatusCreated)
	}
	if create.got.Code != "SKU-001" {
		t.Fatalf("Code = %q", create.got.Code)
	}
	if create.got.Name != "Kampas Rem" {
		t.Fatalf("Name = %q", create.got.Name)
	}
	if create.got.Brand != "Honda" {
		t.Fatalf("Brand = %q", create.got.Brand)
	}
	if create.got.Size == nil || *create.got.Size != 14 {
		t.Fatalf("Size = %v", create.got.Size)
	}
	if create.got.SalePriceRupiah != 40000 {
		t.Fatalf("SalePriceRupiah = %d", create.got.SalePriceRupiah)
	}
	if create.got.ReorderPointQty == nil || *create.got.ReorderPointQty != 5 {
		t.Fatalf("ReorderPointQty = %v", create.got.ReorderPointQty)
	}
	if create.got.CriticalThresholdQty == nil || *create.got.CriticalThresholdQty != 2 {
		t.Fatalf("CriticalThresholdQty = %v", create.got.CriticalThresholdQty)
	}
	if create.got.ActorID != "actor-1" {
		t.Fatalf("ActorID = %q", create.got.ActorID)
	}
	if create.got.Reason != "created by API" {
		t.Fatalf("Reason = %q", create.got.Reason)
	}
}

func TestProductCatalogHandlerListParsesQuery(t *testing.T) {
	list := &fakeListProducts{}
	handler := NewProductCatalogHandler(list, nil, nil, nil, nil, nil, nil, nil)

	req := httptest.NewRequest(http.MethodGet, "/products?q=kampas&status=deleted&page=2&per_page=10", nil)
	rec := httptest.NewRecorder()
	c := echo.New().NewContext(req, rec)

	if err := handler.List(c); err != nil {
		t.Fatalf("List() error = %v", err)
	}

	if rec.Code != http.StatusOK {
		t.Fatalf("status = %d, want %d", rec.Code, http.StatusOK)
	}
	if list.got.Search != "kampas" {
		t.Fatalf("Search = %q", list.got.Search)
	}
	if list.got.Status != "deleted" {
		t.Fatalf("Status = %q", list.got.Status)
	}
	if list.got.Page != 2 {
		t.Fatalf("Page = %d", list.got.Page)
	}
	if list.got.PerPage != 10 {
		t.Fatalf("PerPage = %d", list.got.PerPage)
	}
}

func TestProductCatalogHandlerShowMapsNotFound(t *testing.T) {
	show := &fakeGetProductDetail{err: ports.ErrProductNotFound}
	handler := NewProductCatalogHandler(nil, nil, show, nil, nil, nil, nil, nil)

	req := httptest.NewRequest(http.MethodGet, "/products/missing", nil)
	rec := httptest.NewRecorder()
	c := echo.New().NewContext(req, rec)
	c.SetParamNames("id")
	c.SetParamValues("missing")

	err := handler.Show(c)
	httpErr, ok := err.(*echo.HTTPError)
	if !ok {
		t.Fatalf("Show() error = %T, want *echo.HTTPError", err)
	}
	if httpErr.Code != http.StatusNotFound {
		t.Fatalf("status = %d, want %d", httpErr.Code, http.StatusNotFound)
	}
}

type fakeCreateProduct struct {
	got productcatalogusecase.CreateProductCommand
}

func (f *fakeCreateProduct) Execute(
	_ context.Context,
	cmd productcatalogusecase.CreateProductCommand,
) (productcatalogusecase.CreateProductResult, error) {
	f.got = cmd
	now := time.Date(2026, 6, 12, 10, 0, 0, 0, time.UTC)

	return productcatalogusecase.CreateProductResult{
		ID:                   "product-1",
		Code:                 stringPtr(cmd.Code),
		Name:                 cmd.Name,
		NormalizedName:       "kampas rem",
		Brand:                cmd.Brand,
		NormalizedBrand:      "honda",
		Size:                 cmd.Size,
		SalePriceRupiah:      cmd.SalePriceRupiah,
		ReorderPointQty:      cmd.ReorderPointQty,
		CriticalThresholdQty: cmd.CriticalThresholdQty,
		Status:               "active",
		CreatedAt:            now,
		UpdatedAt:            now,
	}, nil
}

type fakeListProducts struct {
	got productcatalogusecase.ListProductsQuery
}

func (f *fakeListProducts) Execute(
	_ context.Context,
	query productcatalogusecase.ListProductsQuery,
) (productcatalogusecase.ListProductsResult, error) {
	f.got = query

	return productcatalogusecase.ListProductsResult{
		Items: []productcatalogusecase.ListProductsItem{
			{
				ID:              "product-1",
				Code:            stringPtr("SKU-001"),
				Name:            "Kampas Rem",
				Brand:           "Honda",
				Size:            intPtr(14),
				SalePriceRupiah: 40000,
				Status:          "deleted",
			},
		},
	}, nil
}

type fakeGetProductDetail struct {
	err error
}

func (f *fakeGetProductDetail) Execute(
	_ context.Context,
	_ productcatalogusecase.GetProductDetailQuery,
) (productcatalogusecase.GetProductDetailResult, error) {
	return productcatalogusecase.GetProductDetailResult{}, f.err
}

func stringPtr(value string) *string {
	if value == "" {
		return nil
	}

	return &value
}

func intPtr(value int) *int {
	return &value
}
