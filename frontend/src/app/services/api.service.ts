import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private baseUrl = 'http://localhost:9090';

  constructor(private http: HttpClient) { }

  login(dados: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/login.php`, dados);
  }

  getCarros(): Observable<any> {
    return this.http.get(`${this.baseUrl}/carros.php`);
  }

  cadastrarCarro(dados: any): Observable<any> {
    return this.http.post(`${this.baseUrl}/carros.php`, dados);
  }

  getAuxiliares(tipo: string): Observable<any> {
    return this.http.get(`${this.baseUrl}/auxiliares.php?acao=${tipo}`);
  }
}
