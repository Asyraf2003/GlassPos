package http

import (
	"context"

	productcatalogusecase "pos-go/internal/modules/productcatalog/usecase"
)

type listProducts interface {
	Execute(
		ctx context.Context,
		query productcatalogusecase.ListProductsQuery,
	) (productcatalogusecase.ListProductsResult, error)
}

type lookupProducts interface {
	Execute(
		ctx context.Context,
		query productcatalogusecase.LookupProductsQuery,
	) (productcatalogusecase.LookupProductsResult, error)
}

type getProductDetail interface {
	Execute(
		ctx context.Context,
		query productcatalogusecase.GetProductDetailQuery,
	) (productcatalogusecase.GetProductDetailResult, error)
}

type createProduct interface {
	Execute(
		ctx context.Context,
		cmd productcatalogusecase.CreateProductCommand,
	) (productcatalogusecase.CreateProductResult, error)
}

type updateProduct interface {
	Execute(
		ctx context.Context,
		cmd productcatalogusecase.UpdateProductCommand,
	) (productcatalogusecase.UpdateProductResult, error)
}

type softDeleteProduct interface {
	Execute(
		ctx context.Context,
		cmd productcatalogusecase.SoftDeleteProductCommand,
	) (productcatalogusecase.SoftDeleteProductResult, error)
}

type restoreProduct interface {
	Execute(
		ctx context.Context,
		cmd productcatalogusecase.RestoreProductCommand,
	) (productcatalogusecase.RestoreProductResult, error)
}

type listProductVersions interface {
	Execute(
		ctx context.Context,
		query productcatalogusecase.ListProductVersionsQuery,
	) (productcatalogusecase.ListProductVersionsResult, error)
}
