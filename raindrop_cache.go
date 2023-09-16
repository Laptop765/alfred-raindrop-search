package main

import (
	"net/http"

	"github.com/gregjones/httpcache"
	"github.com/gregjones/httpcache/diskcache"
)


func init_cache(cacheDir string) *diskcache.Cache {
	return diskcache.New(cacheDir)
}

func get_cached_http_client() *http.Client {
	tp := httpcache.NewTransport(httpDiskCache)
	return &http.Client{Transport: tp}
}