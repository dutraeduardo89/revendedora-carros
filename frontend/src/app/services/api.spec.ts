import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideRouter } from '@angular/router';
import { ApiService } from './api';
import { AuthService } from './auth.service';

describe('ApiService', () => {
  it('envia o token e não envia o login no cabeçalho', () => {
    localStorage.clear();
    TestBed.configureTestingModule({
      providers: [provideRouter([]), provideHttpClient(), provideHttpClientTesting()],
    });
    const auth = TestBed.inject(AuthService);
    auth.login('loja', 'abc123');
    const api = TestBed.inject(ApiService);
    const http = TestBed.inject(HttpTestingController);
    api.getCarros().subscribe();
    const req = http.expectOne('http://localhost:9090/carros.php');
    expect(req.request.headers.get('Authorization')).toBe('Bearer abc123');
    expect(req.request.headers.has('X-User-Login')).toBe(false);
    req.flush([]);
  });
});
