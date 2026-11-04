@extends('errors::minimal')

@section('title', 'Akses Ditolak')
@section('code', '403')
@section('icon', 'shield-lock')
@section('message', 'Akses Ditolak')
@section('description', ($exception->getMessage() && $exception->getMessage() !== 'Forbidden' && ! str_starts_with($exception->getMessage(), 'User does not have'))
    ? $exception->getMessage()
    : 'Anda tidak memiliki izin untuk membuka halaman ini. Hubungi admin bila menurut Anda ini keliru.')
